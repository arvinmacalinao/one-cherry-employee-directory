<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

/**
 * Org-wide master data — no company assignment. See architecture-plan.md §2.5.
 */
class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['hr_ref_id' => 201, 'name' => 'Product Design'],
            ['hr_ref_id' => 202, 'name' => 'Engineering'],
            ['hr_ref_id' => 203, 'name' => 'IT Support'],
            ['hr_ref_id' => 204, 'name' => 'Human Resources'],
            ['hr_ref_id' => 205, 'name' => 'Finance & Accounting'],
            ['hr_ref_id' => 206, 'name' => 'Sales & Business Development'],
            ['hr_ref_id' => 207, 'name' => 'Marketing'],
            ['hr_ref_id' => 208, 'name' => 'Customer Experience'],
            ['hr_ref_id' => 209, 'name' => 'Legal & Compliance'],
            ['hr_ref_id' => 210, 'name' => 'Operations'],
            ['hr_ref_id' => 211, 'name' => 'Property Management'],
        ];

        foreach ($departments as $department) {
            Department::updateOrCreate(
                ['hr_ref_id' => $department['hr_ref_id']],
                ['name' => $department['name'], 'is_active' => true],
            );
        }
    }
}
