<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeProfile;
use App\Repositories\Contracts\EmployeeRepositoryInterface;

class EmployeeProfileService
{
    /**
     * The only fields a self-service edit (or an Admin editing the "Additional" tab) may touch.
     * Enforced here, not just in the UI — HR-controlled columns on `employees` are never accepted,
     * even if present in the incoming array. See architecture-plan.md §2.4 for the ownership split.
     */
    protected const EDITABLE_FIELDS = [
        'suffix', 'nickname', 'gender', 'birthday', 'name_pronunciation',
        'personal_email', 'mobile_number', 'viber_number', 'telephone', 'local_extension',
        'office_seat', 'office_location_id', 'about_me', 'facebook_url', 'linkedin_url',
        'emergency_contact_name', 'emergency_contact_relationship', 'emergency_contact_phone',
    ];

    public function __construct(
        protected EmployeeRepositoryInterface $employees,
    ) {}

    public function show(int $employeeId): ?Employee
    {
        return Employee::with([
            'company', 'department', 'designation', 'profile.officeLocation',
            'supervisor', 'reports', 'skills',
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

    public function syncSkills(Employee $employee, array $skillNames): void
    {
        $skillIds = collect($skillNames)
            ->filter()
            ->map(fn (string $name) => \App\Models\Skill::firstOrCreate(['name' => trim($name)])->id);

        $employee->skills()->sync($skillIds);
    }
}
