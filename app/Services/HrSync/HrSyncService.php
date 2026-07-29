<?php

namespace App\Services\HrSync;

use App\Enums\SyncStatus;
use App\Enums\SyncType;
use App\Models\ApiSyncLog;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeStatus;
use App\Models\User;
use App\Services\HrSync\Contracts\HrSourceInterface;
use App\Services\HrSync\DTOs\HrEmployeeData;
use App\Services\HrSync\DTOs\SyncPreviewResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Pull-based HR sync: HR System -> REST API -> Laravel Scheduler -> this service -> DB.
 * See architecture-plan.md §2.5 for the full field mapping and design rationale — this
 * is the application's core feature, not a peripheral integration.
 *
 * Depends on HrSourceInterface, not a concrete HTTP client, so the HR integration —
 * and later, Active Directory / Google Workspace adapters — can change without this
 * orchestration logic changing.
 *
 * Company, Department, Designation, and Employment Status are all resolved the same
 * ID-first, name-fallback way via resolveNamedLookup() / resolveEmployeeStatus() —
 * every one of them is a synced lookup table now, not a hardcoded translation.
 * All four are org-wide (Department/Designation are shared master data, not
 * scoped to a company — HR reuses their numeric IDs across companies precisely
 * because it's the same department/designation, not a per-company duplicate).
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
            'warnings' => [],
            'errors' => [],
        ]);

        $counts = ['imported' => 0, 'updated' => 0, 'promoted' => 0, 'status_changed' => 0, 'deactivated' => 0];
        $warnings = [];
        $errors = [];

        try {
            $records = $this->source->fetchEmployees();
            $seenCodes = [];

            // Pass 1: upsert identity + org fields for every record.
            foreach ($records as $data) {
                $seenCodes[] = $data->employeeCode;

                try {
                    // NOT an arrow function: `fn()` auto-captures $warnings *by value*, so the
                    // &$warnings reference parameter inside upsertEmployee would silently mutate
                    // a throwaway copy instead of this loop's $warnings — `use (&$warnings)` is required.
                    $result = DB::transaction(function () use ($data, &$warnings) {
                        return $this->upsertEmployee($data, $warnings);
                    });
                    $counts[$result]++;
                } catch (Throwable $e) {
                    Log::warning("HR sync: failed to upsert {$data->employeeCode}", ['exception' => $e]);
                    $errors[] = "Failed to sync {$data->employeeCode}: {$e->getMessage()}";
                }
            }

            // Pass 2: resolve supervisors, now that every employee_id from this run exists.
            foreach ($records as $data) {
                if ($data->supervisorEmployeeCode) {
                    $this->resolveSupervisor($data, $warnings);
                }
            }

            $counts['deactivated'] = $this->deactivateMissingEmployees($seenCodes);

            $log->update([
                'completed_at' => now(),
                'status' => empty($errors) ? SyncStatus::Success : SyncStatus::Partial,
                'records_imported' => $counts['imported'],
                'records_updated' => $counts['updated'],
                'records_promoted' => $counts['promoted'],
                'records_status_changed' => $counts['status_changed'],
                'records_deactivated' => $counts['deactivated'],
                'warnings' => $warnings,
                'errors' => $errors,
            ]);
        } catch (Throwable $e) {
            Log::error('HR sync run failed', ['exception' => $e]);
            $log->update([
                'completed_at' => now(),
                'status' => SyncStatus::Failed,
                'warnings' => $warnings,
                'errors' => [...$errors, "Sync aborted: {$e->getMessage()}"],
            ]);
        }

        return $log->refresh();
    }

    /**
     * Pure read/diff — never persists anything, not even the lookup auto-creates
     * that a real sync would perform. Mandatory before the first live sync; a
     * generally useful "what would this do" tool afterward. See architecture-plan.md §2.5.
     */
    public function preview(): SyncPreviewResult
    {
        $warnings = [];
        $newEmployees = [];
        $updatedEmployees = [];
        $departmentChanges = [];
        $designationChanges = [];
        $supervisorChanges = [];
        $statusChanges = [];
        $seenCodes = [];

        $records = $this->source->fetchEmployees();

        foreach ($records as $data) {
            $seenCodes[] = $data->employeeCode;
            $name = trim("{$data->firstName} {$data->lastName}");

            $company = $this->resolveNamedLookup(Company::class, $data->companyId, $data->companyName, false, $warnings);
            $department = $this->resolveNamedLookup(Department::class, $data->departmentId, $data->departmentName, false, $warnings);
            $designation = $this->resolveNamedLookup(Designation::class, $data->designationId, $data->designationName, false, $warnings);
            $status = $this->resolveEmployeeStatus($data->employmentStatusId, $data->employmentStatusName, false, $warnings);

            $existing = Employee::with(['company', 'department', 'designation', 'status', 'supervisor'])
                ->where('employee_id', $data->employeeCode)->first();

            if (! $existing) {
                $newEmployees[] = [
                    'employee_id' => $data->employeeCode,
                    'name' => $name,
                    'company' => $company['name'],
                    'department' => $department['name'],
                    'designation' => $designation['name'],
                ];

                continue;
            }

            $changedFields = [];

            if ($department['name'] !== $existing->department?->name) {
                $departmentChanges[] = ['employee_id' => $data->employeeCode, 'name' => $name, 'from' => $existing->department?->name, 'to' => $department['name']];
                $changedFields[] = 'department';
            }

            if ($designation['name'] !== $existing->designation?->name) {
                $designationChanges[] = ['employee_id' => $data->employeeCode, 'name' => $name, 'from' => $existing->designation?->name, 'to' => $designation['name']];
                $changedFields[] = 'designation';
            }

            if ($data->supervisorEmployeeCode !== $existing->supervisor?->employee_id) {
                $supervisorChanges[] = ['employee_id' => $data->employeeCode, 'name' => $name, 'from' => $existing->supervisor?->full_name, 'to' => $data->supervisorEmployeeCode];
                $changedFields[] = 'supervisor';
            }

            if ($status['name'] !== $existing->status?->name) {
                $statusChanges[] = ['employee_id' => $data->employeeCode, 'name' => $name, 'from' => $existing->status?->name, 'to' => $status['name']];
                $changedFields[] = 'employment status';
            }

            if ($existing->first_name !== $data->firstName
                || $existing->last_name !== $data->lastName
                || $existing->email !== $data->email
                || $company['name'] !== $existing->company?->name) {
                $changedFields[] = 'identity';
            }

            if (! empty($changedFields)) {
                $updatedEmployees[] = ['employee_id' => $data->employeeCode, 'name' => $name, 'fields_changed' => $changedFields];
            }
        }

        $becomingInactive = Employee::visibleInDirectory()
            ->whereNotIn('employee_id', $seenCodes)
            ->get(['employee_id', 'first_name', 'last_name'])
            ->map(fn (Employee $e) => ['employee_id' => $e->employee_id, 'name' => $e->full_name])
            ->all();

        return new SyncPreviewResult(
            newEmployees: $newEmployees,
            updatedEmployees: $updatedEmployees,
            departmentChanges: $departmentChanges,
            designationChanges: $designationChanges,
            supervisorChanges: $supervisorChanges,
            statusChanges: $statusChanges,
            becomingInactive: $becomingInactive,
            warnings: $warnings,
        );
    }

    /**
     * @return string one of: imported, updated, promoted, status_changed
     */
    protected function upsertEmployee(HrEmployeeData $data, array &$warnings): string
    {
        $company = $this->resolveNamedLookup(Company::class, $data->companyId, $data->companyName, true, $warnings);
        $department = $this->resolveNamedLookup(Department::class, $data->departmentId, $data->departmentName, true, $warnings);
        $designation = $this->resolveNamedLookup(Designation::class, $data->designationId, $data->designationName, true, $warnings);
        $status = $this->resolveEmployeeStatus($data->employmentStatusId, $data->employmentStatusName, true, $warnings);

        $attributes = [
            'first_name' => $data->firstName,
            'middle_name' => $data->middleName,
            'last_name' => $data->lastName,
            'username' => $data->username,
            'company_id' => $company['id'],
            'department_id' => $department['id'],
            'designation_id' => $designation['id'],
            'date_hired' => $data->dateHired,
            'date_regularized' => $data->dateRegularized,
            'date_separated' => $data->dateSeparated,
            'is_active' => true,
            'last_synced_at' => now(),
        ];

        if ($status['id']) {
            $attributes['employee_status_id'] = $status['id'];
        }

        // Email is HR-owned *when HR actually provides one* — but HR frequently
        // sends null, and an Admin can fill the gap in from /admin/employees.
        // Only ever overwrite it here when HR sends a real value, so a sync never
        // clobbers an Admin-entered email back to blank. If HR later does provide
        // one, HR's value wins again on the next sync — this is a fallback, not
        // a permanent hand-off of ownership.
        if ($data->email !== null) {
            $attributes['email'] = $data->email;
        }

        $existing = Employee::withTrashed()->where('employee_id', $data->employeeCode)->first();

        if (! $existing) {
            $employee = Employee::create(['employee_id' => $data->employeeCode, ...$attributes]);
            $employee->profile()->create(['employee_id' => $employee->id]);

            return 'imported';
        }

        if ($existing->trashed()) {
            $existing->restore();
        }

        $wasDesignationId = $existing->designation_id;
        $wasStatusId = $existing->employee_status_id;

        $existing->update($attributes);

        if ($designation['id'] && $wasDesignationId !== $designation['id']) {
            return 'promoted';
        }

        if ($status['id'] && $wasStatusId !== $status['id']) {
            return 'status_changed';
        }

        return 'updated';
    }

    /**
     * Runs after every employee_id from this run has been upserted (pass 1), so a
     * supervisor who appears later in the same feed than their report can still
     * be resolved. Self-heals on a later run if the supervisor isn't found now.
     */
    protected function resolveSupervisor(HrEmployeeData $data, array &$warnings): void
    {
        $employee = Employee::where('employee_id', $data->employeeCode)->first();

        if (! $employee) {
            return;
        }

        $supervisor = Employee::where('employee_id', $data->supervisorEmployeeCode)->first();

        if (! $supervisor) {
            $warnings[] = "Could not resolve supervisor \"{$data->supervisorEmployeeCode}\" for {$data->employeeCode} — not found in this sync run.";

            return;
        }

        if ($employee->immediate_supervisor_id !== $supervisor->id) {
            $employee->update(['immediate_supervisor_id' => $supervisor->id]);
        }
    }

    /**
     * Any employee currently is_active=true in OCED but absent from this run's feed
     * entirely is marked inactive — the sole directory-visibility signal (§2.5),
     * covering employees HR has fully deactivated (u_active=0), which disappear
     * from the feed rather than arriving with a "departed" status to read.
     */
    protected function deactivateMissingEmployees(array $seenCodes): int
    {
        $missing = Employee::visibleInDirectory()->whereNotIn('employee_id', $seenCodes)->get();

        foreach ($missing as $employee) {
            $employee->update(['is_active' => false]);
        }

        return $missing->count();
    }

    /**
     * ID-first, name-fallback resolution shared by Company/Department/Designation —
     * all three are synced lookup tables with the same shape (hr_ref_id, name,
     * is_active, needs_review), and all three are org-wide, not scoped to a
     * company: there is one "Sales" department across the whole Group, not one
     * per company — an employee's company comes solely from Employee::company_id.
     * A name match opportunistically backfills hr_ref_id so the *next* sync for
     * this record goes straight through the ID path. When $persist is false
     * (Sync Preview), nothing is written — an unresolved identity is reported
     * back as "would create" instead of actually being created.
     *
     * @param  class-string<Company|Department|Designation>  $modelClass
     * @return array{id: ?int, name: ?string, is_new: bool}
     */
    protected function resolveNamedLookup(string $modelClass, ?int $hrRefId, ?string $name, bool $persist, array &$warnings): array
    {
        if (! $hrRefId && ! $name) {
            return ['id' => null, 'name' => null, 'is_new' => false];
        }

        if ($hrRefId) {
            $match = $modelClass::where('hr_ref_id', $hrRefId)->first();

            if ($match) {
                return ['id' => $match->id, 'name' => $match->name, 'is_new' => false];
            }
        }

        if ($name) {
            $match = $modelClass::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();

            if ($match) {
                if ($persist && $hrRefId && ! $match->hr_ref_id) {
                    $match->update(['hr_ref_id' => $hrRefId]);
                }

                return ['id' => $match->id, 'name' => $match->name, 'is_new' => false];
            }
        }

        if (! $persist) {
            return ['id' => null, 'name' => $name ?? "HR #{$hrRefId}", 'is_new' => true];
        }

        $attributes = [
            'hr_ref_id' => $hrRefId,
            'name' => $name,
            'is_active' => true,
            'needs_review' => true,
        ];

        if ($modelClass === Company::class) {
            $attributes['slug'] = Str::slug($name).'-'.Str::random(4);
        }

        $created = $modelClass::create($attributes);
        $warnings[] = 'Auto-created new '.class_basename($modelClass)." \"{$name}\" from HR — needs Admin review.";

        return ['id' => $created->id, 'name' => $created->name, 'is_new' => true];
    }

    /**
     * Employment status has no name-fallback (HR always sends an id for it) and
     * is never scoped to a company. See architecture-plan.md §2.5.
     *
     * @return array{id: ?int, name: ?string, is_new: bool}
     */
    protected function resolveEmployeeStatus(?int $hrRefId, ?string $name, bool $persist, array &$warnings): array
    {
        if (! $hrRefId) {
            $warnings[] = 'Employee record missing employment_status.id — status left unchanged.';

            return ['id' => null, 'name' => $name, 'is_new' => false];
        }

        $status = EmployeeStatus::where('hr_ref_id', $hrRefId)->first();

        if ($status) {
            return ['id' => $status->id, 'name' => $status->name, 'is_new' => false];
        }

        $label = $name ?? "Status #{$hrRefId}";

        if (! $persist) {
            return ['id' => null, 'name' => $label, 'is_new' => true];
        }

        $status = EmployeeStatus::create(['hr_ref_id' => $hrRefId, 'name' => $label]);

        return ['id' => $status->id, 'name' => $status->name, 'is_new' => true];
    }
}
