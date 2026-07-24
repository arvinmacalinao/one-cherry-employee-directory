<?php

namespace App\Services\HrSync;

use App\Enums\EmployeeSource;
use App\Enums\EmploymentStatus;
use App\Enums\SyncStatus;
use App\Enums\SyncType;
use App\Models\ApiSyncLog;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\User;
use App\Services\HrSync\Contracts\HrSourceInterface;
use App\Services\HrSync\DTOs\HrEmployeeData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Pull-based HR sync: HR System -> REST API -> Laravel Scheduler -> this service -> DB.
 * See architecture-plan.md §2.4 and §7 for the full field mapping and design rationale.
 *
 * Depends on HrSourceInterface, not a concrete HTTP client, so the HR integration —
 * and later, Active Directory / Google Workspace adapters — can change without this
 * orchestration logic changing.
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
                    $result = DB::transaction(fn () => $this->upsertEmployee($data, $errors));
                    $counts[$result]++;
                } catch (Throwable $e) {
                    Log::warning("HR sync: failed to upsert {$data->employeeCode}", ['exception' => $e]);
                    $errors[] = "Failed to sync {$data->employeeCode}: {$e->getMessage()}";
                }
            }

            $this->resolveSupervisors($records);
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
        $company = $this->resolveCompany($data->companyHrRefId, $errors);
        $department = $this->resolveDepartment($data->departmentHrRefId, $company, $errors);
        $designation = $this->resolveDesignation($data->designationHrRefId, $company, $errors);
        $status = $this->resolveStatus($data->employmentStatusCode, $errors);

        $attributes = [
            'first_name' => $data->firstName,
            'middle_name' => $data->middleName,
            'last_name' => $data->lastName,
            'email' => $data->email,
            'company_id' => $company->id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'employment_status' => $status,
            'date_hired' => $data->dateHired,
            'date_regularized' => $data->dateRegularized,
            'date_separated' => $data->dateSeparated,
            'job_level' => $data->jobLevel,
            'last_synced_at' => now(),
        ];

        $existing = Employee::withTrashed()->where('employee_id', $data->employeeCode)->first();

        if (! $existing) {
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

        if ($wasDesignation !== $designation->id) {
            return 'transferred';
        }

        $enteredResignedOrInactive = in_array($status, [EmploymentStatus::Resigned, EmploymentStatus::Inactive], true)
            && in_array($wasStatus, [EmploymentStatus::Active, EmploymentStatus::OnLeave], true);

        return $enteredResignedOrInactive ? 'deactivated' : 'updated';
    }

    /**
     * Any employee currently active/on_leave in OCED but absent from this run's feed
     * entirely is marked inactive — HR no longer confirms them, distinct from an
     * explicit resignation (which arrives as an employment_status change instead).
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
     * @param  \Illuminate\Support\Collection<int, HrEmployeeData>  $records
     */
    protected function resolveSupervisors($records): void
    {
        foreach ($records as $data) {
            if (! $data->supervisorEmployeeCode) {
                continue;
            }

            $employee = Employee::where('employee_id', $data->employeeCode)->first();
            $supervisor = Employee::where('employee_id', $data->supervisorEmployeeCode)->first();

            if ($employee && $supervisor && $employee->immediate_supervisor_id !== $supervisor->id) {
                $employee->update(['immediate_supervisor_id' => $supervisor->id]);
            }
        }
    }

    /**
     * Identity + admin-curated metadata pattern (architecture-plan.md §2.4): sync only ever
     * writes `name`. If the hr_ref_id has never been seen before, a stub is auto-created and
     * flagged so an Admin can fill in the branding/description later — sync never blocks on it.
     */
    protected function resolveCompany(int $hrRefId, array &$errors): Company
    {
        $company = Company::where('hr_ref_id', $hrRefId)->first();

        if (! $company) {
            $company = Company::create([
                'hr_ref_id' => $hrRefId,
                'name' => "Unmapped Company #{$hrRefId}",
                'slug' => "unmapped-company-{$hrRefId}",
                'is_active' => true,
            ]);
            $errors[] = "Auto-created stub Company #{$hrRefId} — needs Admin review.";
        }

        return $company;
    }

    protected function resolveDepartment(int $hrRefId, Company $company, array &$errors): Department
    {
        $department = Department::where('hr_ref_id', $hrRefId)->first();

        if (! $department) {
            $department = Department::create([
                'hr_ref_id' => $hrRefId,
                'company_id' => $company->id,
                'name' => "Unmapped Department #{$hrRefId}",
                'is_active' => true,
            ]);
            $errors[] = "Auto-created stub Department #{$hrRefId} — needs Admin review.";
        }

        return $department;
    }

    protected function resolveDesignation(int $hrRefId, Company $company, array &$errors): Designation
    {
        $designation = Designation::where('hr_ref_id', $hrRefId)->first();

        if (! $designation) {
            $designation = Designation::create([
                'hr_ref_id' => $hrRefId,
                'company_id' => $company->id,
                'name' => "Unmapped Designation #{$hrRefId}",
                'hierarchy_level' => 1,
                'is_active' => true,
            ]);
            $errors[] = "Auto-created stub Designation #{$hrRefId} — needs Admin review.";
        }

        return $designation;
    }

    protected function resolveStatus(int|string $code, array &$errors): EmploymentStatus
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
