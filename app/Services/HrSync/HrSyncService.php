<?php

namespace App\Services\HrSync;

use App\Enums\EmployeeSource;
use App\Enums\EmploymentStatus;
use App\Enums\SyncStatus;
use App\Enums\SyncType;
use App\Models\ApiSyncLog;
use App\Models\Company;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\User;
use App\Services\HrSync\Contracts\HrSourceInterface;
use App\Services\HrSync\DTOs\HrEmployeeData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Pull-based HR sync: HR System -> REST API -> Laravel Scheduler -> this service -> DB.
 * See architecture-plan.md §2.4 and §7 for the full field mapping and design rationale.
 *
 * Depends on HrSourceInterface, not a concrete HTTP client, so the HR integration —
 * and later, Active Directory / Google Workspace adapters — can change without this
 * orchestration logic changing.
 *
 * HR only actually provides: employee_id, name, email, company, designation, status
 * (see HrEmployeeData). Department, dates, job level, and supervisor are NOT synced —
 * they're Admin-managed fields on the same `employees` row. Company/Designation are
 * matched by HR's numeric company_id/designation_id when the API sends them
 * (companies.hr_ref_id / designations.hr_ref_id), falling back to a case-insensitive
 * name match when it doesn't — see resolveCompany()/resolveDesignation().
 */
class HrSyncService
{
    public function __construct(
        protected HrSourceInterface $source,
    ) {}

    public function sync(SyncType $type, ?User $triggeredBy = null): ApiSyncLog
    {
        $log = ApiSyncLog::create([
            'sync_type' => $type,
            'started_at' => now(),
            'triggered_by' => $triggeredBy?->id,
            'errors' => [],
        ]);

        $counts = ['imported' => 0, 'updated' => 0, 'transferred' => 0, 'deactivated' => 0];
        $errors = [];

        try {
            $records = $this->source->fetchEmployees();
            $seenCodes = [];

            foreach ($records as $data) {
                $seenCodes[] = $data->employeeCode;

                try {
                    // NOT an arrow function: `fn()` auto-captures $errors *by value*, so the
                    // &$errors reference parameter inside upsertEmployee would silently mutate
                    // a throwaway copy instead of this loop's $errors — `use (&$errors)` is required.
                    $result = DB::transaction(function () use ($data, &$errors) {
                        return $this->upsertEmployee($data, $errors);
                    });
                    $counts[$result]++;
                } catch (Throwable $e) {
                    Log::warning("HR sync: failed to upsert {$data->employeeCode}", ['exception' => $e]);
                    $errors[] = "Failed to sync {$data->employeeCode}: {$e->getMessage()}";
                }
            }

            $counts['deactivated'] += $this->deactivateMissingEmployees($seenCodes);

            $log->update([
                'completed_at' => now(),
                'status' => empty($errors) ? SyncStatus::Success : SyncStatus::Partial,
                'records_imported' => $counts['imported'],
                'records_updated' => $counts['updated'],
                'records_transferred' => $counts['transferred'],
                'records_deactivated' => $counts['deactivated'],
                'errors' => $errors,
            ]);
        } catch (Throwable $e) {
            Log::error('HR sync run failed', ['exception' => $e]);
            $log->update([
                'completed_at' => now(),
                'status' => SyncStatus::Failed,
                'errors' => [...$errors, "Sync aborted: {$e->getMessage()}"],
            ]);
        }

        return $log->refresh();
    }

    /**
     * @return string one of: imported, updated, transferred, deactivated
     */
    protected function upsertEmployee(HrEmployeeData $data, array &$errors): string
    {
        $company = $this->resolveCompany($data->companyId, $data->companyName, $errors);
        $designation = $this->resolveDesignation($data->designationId, $data->designationName, $company, $errors);
        $status = $this->resolveStatus($data->employmentStatusCode, $errors);

        // Department, dates, job level, supervisor, middle name are deliberately absent
        // here — HR doesn't provide them, so sync never touches them either way.
        $attributes = [
            'first_name' => $data->firstName,
            'last_name' => $data->lastName,
            'email' => $data->email,
            'company_id' => $company->id,
            'employment_status' => $status,
            'last_synced_at' => now(),
        ];

        if ($designation) {
            $attributes['designation_id'] = $designation->id;
        }

        $existing = Employee::withTrashed()->where('employee_id', $data->employeeCode)->first();

        if (! $existing) {
            if (! $designation) {
                throw new RuntimeException('cannot create — HR did not provide a designation for this employee');
            }

            $employee = Employee::create([
                'employee_id' => $data->employeeCode,
                'source' => EmployeeSource::HrSync,
                ...$attributes,
            ]);
            $employee->profile()->create(['employee_id' => $employee->id]);

            return 'imported';
        }

        if ($existing->trashed()) {
            $existing->restore();
        }

        $wasDesignation = $existing->designation_id;
        $wasStatus = $existing->employment_status;

        $existing->update($attributes);

        if ($designation && $wasDesignation !== $designation->id) {
            return 'transferred';
        }

        $enteredResignedOrInactive = in_array($status, [EmploymentStatus::Resigned, EmploymentStatus::Inactive], true)
            && in_array($wasStatus, [EmploymentStatus::Active, EmploymentStatus::OnLeave], true);

        return $enteredResignedOrInactive ? 'deactivated' : 'updated';
    }

    /**
     * Any employee currently active/on_leave in OCED but absent from this run's feed
     * entirely is marked inactive. This is now the *primary* way a departure is
     * detected — the live HR feed only ever contains active employees (it filters
     * server-side), so there's no separate "resigned" status to read from it.
     */
    protected function deactivateMissingEmployees(array $seenCodes): int
    {
        $missing = Employee::visibleInDirectory()->whereNotIn('employee_id', $seenCodes)->get();

        foreach ($missing as $employee) {
            $employee->update(['employment_status' => EmploymentStatus::Inactive]);
        }

        return $missing->count();
    }

    /**
     * Companies are matched by HR's numeric company_id (via hr_ref_id) when the
     * API sends one — stable across renames on either side. Falls back to a
     * case-insensitive name match when no ID is given (older HR response shape),
     * or when the ID doesn't match anything yet — that fallback also opportunistically
     * backfills hr_ref_id onto the matched row so the *next* sync for this company
     * can go straight through the ID path. A company HR sends that doesn't exist
     * yet in OCED under either identity is auto-created (with no branding/address
     * yet) and flagged for an Admin to fill in — sync never blocks on it.
     */
    protected function resolveCompany(?int $hrRefId, string $name, array &$errors): Company
    {
        if ($hrRefId) {
            $company = Company::where('hr_ref_id', $hrRefId)->first();

            if ($company) {
                return $company;
            }
        }

        $company = Company::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();

        if ($company) {
            if ($hrRefId && ! $company->hr_ref_id) {
                $company->update(['hr_ref_id' => $hrRefId]);
            }

            return $company;
        }

        $company = Company::create([
            'hr_ref_id' => $hrRefId,
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(4),
            'is_active' => true,
            'needs_review' => true,
        ]);
        $errors[] = "Auto-created new Company \"{$name}\" from HR — needs Admin review (logo, address, etc.).";

        return $company;
    }

    /**
     * Same ID-first, name-fallback matching as resolveCompany(), scoped to the
     * employee's company (same uniqueness scope as the designations table
     * itself). Null when HR sends no designation for this record — the caller
     * decides how to handle that.
     */
    protected function resolveDesignation(?int $hrRefId, ?string $name, Company $company, array &$errors): ?Designation
    {
        if (! $name && ! $hrRefId) {
            return null;
        }

        if ($hrRefId) {
            $designation = Designation::where('company_id', $company->id)
                ->where('hr_ref_id', $hrRefId)
                ->first();

            if ($designation) {
                return $designation;
            }
        }

        if (! $name) {
            return null;
        }

        $designation = Designation::where('company_id', $company->id)
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->first();

        if ($designation) {
            if ($hrRefId && ! $designation->hr_ref_id) {
                $designation->update(['hr_ref_id' => $hrRefId]);
            }

            return $designation;
        }

        $designation = Designation::create([
            'company_id' => $company->id,
            'hr_ref_id' => $hrRefId,
            'name' => $name,
            'hierarchy_level' => 1,
            'is_active' => true,
            'needs_review' => true,
        ]);
        $errors[] = "Auto-created new Designation \"{$name}\" at {$company->name} — needs Admin review (hierarchy level, description).";

        return $designation;
    }

    protected function resolveStatus(string $code, array &$errors): EmploymentStatus
    {
        $map = config('hr_sync.status_map');
        $value = $map[$code] ?? null;

        if (! $value) {
            $errors[] = "Unknown employment status code '{$code}' — defaulted to Active.";

            return EmploymentStatus::Active;
        }

        return EmploymentStatus::from($value);
    }
}
