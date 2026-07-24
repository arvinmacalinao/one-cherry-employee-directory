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
 * Deliberately more forgiving than the real API here: HrRestApiSource's live feed
 * only ever contains status="active" records (see its docblock for the on_leave
 * gap that creates), but this fake echoes back every current employee regardless
 * of status, so a "Sync Now" in local dev never surprises you by deactivating
 * your on_leave demo data.
 *
 * Matches the confirmed real contract: company/designation by name, no department,
 * dates, job level, or supervisor — see HrEmployeeData.
 */
class FakeHrSource implements HrSourceInterface
{
    public function fetchEmployees(): Collection
    {
        $roster = Employee::with(['company', 'designation'])->get()->map(
            fn (Employee $employee) => $this->echoBack($employee)
        );

        return $roster
            ->push($this->syntheticNewHire())
            ->values();
    }

    protected function echoBack(Employee $employee): HrEmployeeData
    {
        $designationName = $employee->designation?->name;

        // Promote the first "Junior Developer" found — demonstrates transfer detection.
        if ($designationName === 'Junior Developer') {
            $designationName = 'Software Engineer';
        }

        return new HrEmployeeData(
            employeeCode: $employee->employee_id,
            firstName: $employee->first_name,
            lastName: $employee->last_name,
            email: $employee->email,
            companyName: $employee->company?->name ?? 'Unknown Company',
            designationName: $designationName,
            employmentStatusCode: $employee->employment_status->value,
        );
    }

    protected function syntheticNewHire(): HrEmployeeData
    {
        $suffix = Str::upper(Str::random(4));

        return new HrEmployeeData(
            employeeCode: "SYNC-{$suffix}",
            firstName: 'New',
            lastName: 'Hire',
            email: "new.hire.{$suffix}@onecherry.group",
            companyName: 'Cherry Digital Solutions',
            designationName: 'Junior Developer',
            employmentStatusCode: 'active',
        );
    }
}
