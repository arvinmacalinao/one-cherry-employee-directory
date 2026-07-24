<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $companiesByHrRef = Company::pluck('id', 'hr_ref_id');

        // 'head' is resolved to department_head_id by EmployeeSeeder, once employees exist.
        $departments = [
            ['hr_ref_id' => 201, 'name' => 'Product Design', 'company' => 102],
            ['hr_ref_id' => 202, 'name' => 'Engineering', 'company' => 102],
            ['hr_ref_id' => 203, 'name' => 'IT Support', 'company' => 102],
            ['hr_ref_id' => 204, 'name' => 'Human Resources', 'company' => 101],
            ['hr_ref_id' => 205, 'name' => 'Finance & Accounting', 'company' => 105],
            ['hr_ref_id' => 206, 'name' => 'Sales & Business Development', 'company' => 106],
            ['hr_ref_id' => 207, 'name' => 'Marketing', 'company' => 101],
            ['hr_ref_id' => 208, 'name' => 'Customer Experience', 'company' => 101],
            ['hr_ref_id' => 209, 'name' => 'Legal & Compliance', 'company' => 103],
            ['hr_ref_id' => 210, 'name' => 'Operations', 'company' => 104],
            ['hr_ref_id' => 211, 'name' => 'Property Management', 'company' => 103],
        ];

        foreach ($departments as $department) {
            Department::updateOrCreate(
                ['hr_ref_id' => $department['hr_ref_id']],
                [
                    'name' => $department['name'],
                    'company_id' => $companiesByHrRef[$department['company']],
                    'is_active' => true,
                ],
            );
        }
    }
}
