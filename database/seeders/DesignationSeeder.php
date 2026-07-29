<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Designation;
use Illuminate\Database\Seeder;

class DesignationSeeder extends Seeder
{
    public function run(): void
    {
        $companiesByHrRef = Company::pluck('id', 'hr_ref_id');

        $designations = [
            ['hr_ref_id' => 301, 'name' => 'Senior UX Designer', 'company' => 102],
            ['hr_ref_id' => 302, 'name' => 'Product Designer', 'company' => 102],
            ['hr_ref_id' => 303, 'name' => 'IT Support Specialist', 'company' => 102],
            ['hr_ref_id' => 304, 'name' => 'Software Engineer', 'company' => 102],
            ['hr_ref_id' => 305, 'name' => 'Backend Engineer', 'company' => 102],
            ['hr_ref_id' => 306, 'name' => 'Junior Developer', 'company' => 102],
            ['hr_ref_id' => 307, 'name' => 'Marketing Manager', 'company' => 101],
            ['hr_ref_id' => 308, 'name' => 'Brand Marketing Specialist', 'company' => 101],
            ['hr_ref_id' => 309, 'name' => 'HR Business Partner', 'company' => 101],
            ['hr_ref_id' => 310, 'name' => 'Recruitment Specialist', 'company' => 101],
            ['hr_ref_id' => 311, 'name' => 'Customer Experience Lead', 'company' => 101],
            ['hr_ref_id' => 312, 'name' => 'Customer Support Associate', 'company' => 101],
            ['hr_ref_id' => 313, 'name' => 'Sales Executive', 'company' => 106],
            ['hr_ref_id' => 314, 'name' => 'Regional Sales Manager', 'company' => 106],
            ['hr_ref_id' => 315, 'name' => 'Financial Analyst', 'company' => 105],
            ['hr_ref_id' => 316, 'name' => 'Accounting Supervisor', 'company' => 105],
            ['hr_ref_id' => 317, 'name' => 'Operations Supervisor', 'company' => 104],
            ['hr_ref_id' => 318, 'name' => 'Warehouse Manager', 'company' => 104],
            ['hr_ref_id' => 319, 'name' => 'Warehouse Associate', 'company' => 104],
            ['hr_ref_id' => 320, 'name' => 'Legal Counsel', 'company' => 103],
            ['hr_ref_id' => 321, 'name' => 'Property Manager', 'company' => 103],
        ];

        foreach ($designations as $designation) {
            Designation::updateOrCreate(
                ['hr_ref_id' => $designation['hr_ref_id']],
                [
                    'name' => $designation['name'],
                    'company_id' => $companiesByHrRef[$designation['company']],
                    'is_active' => true,
                ],
            );
        }
    }
}
