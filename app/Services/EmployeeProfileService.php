<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeProfile;

class EmployeeProfileService
{
    /**
     * The only fields an Admin may ever edit on an employee. Enforced here, not just
     * in the UI — HR-owned columns on `employees` are never accepted, even if present
     * in the incoming array. See architecture-plan.md §2.5, §7 for the ownership split.
     */
    protected const EDITABLE_FIELDS = [
        'birthday', 'viber_number', 'office_location_id', 'about_me',
    ];

    public function show(int $employeeId): ?Employee
    {
        return Employee::with([
            'company', 'department', 'designation', 'status', 'profile.officeLocation',
        ])->find($employeeId);
    }

    public function updateProfile(Employee $employee, array $attributes): EmployeeProfile
    {
        $safeAttributes = array_intersect_key($attributes, array_flip(self::EDITABLE_FIELDS));

        return $employee->profile()->updateOrCreate(
            ['employee_id' => $employee->id],
            $safeAttributes,
        );
    }
}
