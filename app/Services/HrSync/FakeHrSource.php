<?php

namespace App\Services\HrSync;

use App\Models\Employee;
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
 * Swap the config binding for a real HrRestApiSource once HR API credentials exist;
 * HrSyncService itself never needs to change.
 */
class FakeHrSource implements HrSourceInterface
{
    public function fetchEmployees(): Collection
    {
        $roster = Employee::with('department')->get()->map(
            fn (Employee $employee) => $this->echoBack($employee)
        );

        return $roster
            ->push($this->syntheticNewHire())
            ->values();
    }

    protected function echoBack(Employee $employee): HrEmployeeData
    {
        $designationHrRefId = $employee->designation?->hr_ref_id ?? 0;

        // Promote the first "Junior Developer" found — demonstrates transfer detection.
        if ($employee->designation?->name === 'Junior Developer') {
            $designationHrRefId = $employee->designation->hr_ref_id + 1000; // simulated new designation id
        }

        return new HrEmployeeData(
            employeeCode: $employee->employee_id,
            firstName: $employee->first_name,
            middleName: $employee->middle_name,
            lastName: $employee->last_name,
            email: $employee->email,
            companyHrRefId: $employee->company?->hr_ref_id ?? 0,
            departmentHrRefId: $employee->department?->hr_ref_id ?? 0,
            designationHrRefId: $designationHrRefId,
            supervisorEmployeeCode: $employee->supervisor?->employee_id,
            employmentStatusCode: $employee->employment_status->value,
            dateHired: $employee->date_hired?->toDateString(),
            dateRegularized: $employee->date_regularized?->toDateString(),
            dateSeparated: $employee->date_separated?->toDateString(),
            jobLevel: $employee->job_level,
        );
    }

    protected function syntheticNewHire(): HrEmployeeData
    {
        $suffix = Str::upper(Str::random(4));

        return new HrEmployeeData(
            employeeCode: "SYNC-{$suffix}",
            firstName: 'New',
            middleName: null,
            lastName: 'Hire',
            email: "new.hire.{$suffix}@onecherry.group",
            companyHrRefId: 102, // Cherry Digital Solutions
            departmentHrRefId: 202, // Engineering
            designationHrRefId: 306, // Junior Developer
            supervisorEmployeeCode: null,
            employmentStatusCode: 'active',
            dateHired: now()->toDateString(),
            dateRegularized: null,
            dateSeparated: null,
            jobLevel: 1,
        );
    }
}
