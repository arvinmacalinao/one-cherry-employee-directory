<?php

namespace App\Services\HrSync;

use App\Models\Employee;
use App\Models\EmployeeStatus;
use App\Services\HrSync\Contracts\HrSourceInterface;
use App\Services\HrSync\DTOs\HrEmployeeData;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Local-dev / demo stand-in for the real HR REST API, bound when HR_SYNC_SOURCE=fake
 * (see config/hr_sync.php). It echoes back the current roster unchanged — so running
 * "Sync Now" against it is safe and non-destructive — but injects one synthetic new
 * hire and promotes one existing employee, so the new-hire and promotion code paths
 * in HrSyncService are still exercised end-to-end without a real HR system.
 *
 * Matches the current real contract: {id, name} pairs for company/department/
 * designation/employment_status, plus a supervisor {id, employee_id, name} object.
 * echoBack() sends OCED's own IDs as if they were HR's, exercising the ID-match
 * path; the synthetic new hire omits IDs to keep the name-fallback path exercised too.
 */
class FakeHrSource implements HrSourceInterface
{
    public function fetchEmployees(): Collection
    {
        $roster = Employee::with(['company', 'department', 'designation', 'status', 'supervisor'])
            ->get()
            ->map(fn (Employee $employee) => $this->echoBack($employee));

        return $roster
            ->push($this->syntheticNewHire())
            ->values();
    }

    protected function echoBack(Employee $employee): HrEmployeeData
    {
        $designationName = $employee->designation?->name;

        // Promote the first "Junior Developer" found — demonstrates promotion detection.
        $designationId = $employee->designation_id;
        if ($designationName === 'Junior Developer') {
            $designationName = 'Software Engineer';
            $designationId = null; // force a name-fallback match onto the real "Software Engineer" row
        }

        return new HrEmployeeData(
            employeeCode: $employee->employee_id,
            isActiveInHr: $employee->is_active,
            firstName: $employee->first_name,
            middleName: $employee->middle_name,
            lastName: $employee->last_name,
            username: $employee->username,
            email: $employee->email,
            companyId: $employee->company_id,
            companyName: $employee->company?->name ?? 'Unknown Company',
            departmentId: $employee->department_id,
            departmentName: $employee->department?->name,
            designationId: $designationId,
            designationName: $designationName,
            supervisorEmployeeCode: $employee->supervisor?->employee_id,
            employmentStatusId: $employee->employee_status_id,
            employmentStatusName: $employee->status?->name ?? 'Active',
            dateHired: $employee->date_hired?->format('Y-m-d'),
            dateRegularized: $employee->date_regularized?->format('Y-m-d'),
            dateSeparated: $employee->date_separated?->format('Y-m-d'),
        );
    }

    protected function syntheticNewHire(): HrEmployeeData
    {
        $suffix = Str::upper(Str::random(4));
        $status = EmployeeStatus::query()->first();

        return new HrEmployeeData(
            employeeCode: "SYNC-{$suffix}",
            isActiveInHr: true,
            firstName: 'New',
            middleName: null,
            lastName: 'Hire',
            username: 'new.hire.'.strtolower($suffix),
            email: "new.hire.{$suffix}@onecherry.group",
            companyId: null,
            companyName: 'Cherry Digital Solutions',
            departmentId: null,
            departmentName: 'Engineering',
            designationId: null,
            designationName: 'Junior Developer',
            supervisorEmployeeCode: null,
            employmentStatusId: $status?->hr_ref_id ?? 1,
            employmentStatusName: $status?->name ?? 'Active',
            dateHired: now()->format('Y-m-d'),
            dateRegularized: null,
            dateSeparated: null,
        );
    }
}
