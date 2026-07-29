<?php

namespace Database\Seeders;

use App\Models\Designation;
use Illuminate\Database\Seeder;

/**
 * Org-wide master data — no company assignment. See architecture-plan.md §2.5.
 */
class DesignationSeeder extends Seeder
{
    public function run(): void
    {
        $designations = [
            ['hr_ref_id' => 301, 'name' => 'Senior UX Designer'],
            ['hr_ref_id' => 302, 'name' => 'Product Designer'],
            ['hr_ref_id' => 303, 'name' => 'IT Support Specialist'],
            ['hr_ref_id' => 304, 'name' => 'Software Engineer'],
            ['hr_ref_id' => 305, 'name' => 'Backend Engineer'],
            ['hr_ref_id' => 306, 'name' => 'Junior Developer'],
            ['hr_ref_id' => 307, 'name' => 'Marketing Manager'],
            ['hr_ref_id' => 308, 'name' => 'Brand Marketing Specialist'],
            ['hr_ref_id' => 309, 'name' => 'HR Business Partner'],
            ['hr_ref_id' => 310, 'name' => 'Recruitment Specialist'],
            ['hr_ref_id' => 311, 'name' => 'Customer Experience Lead'],
            ['hr_ref_id' => 312, 'name' => 'Customer Support Associate'],
            ['hr_ref_id' => 313, 'name' => 'Sales Executive'],
            ['hr_ref_id' => 314, 'name' => 'Regional Sales Manager'],
            ['hr_ref_id' => 315, 'name' => 'Financial Analyst'],
            ['hr_ref_id' => 316, 'name' => 'Accounting Supervisor'],
            ['hr_ref_id' => 317, 'name' => 'Operations Supervisor'],
            ['hr_ref_id' => 318, 'name' => 'Warehouse Manager'],
            ['hr_ref_id' => 319, 'name' => 'Warehouse Associate'],
            ['hr_ref_id' => 320, 'name' => 'Legal Counsel'],
            ['hr_ref_id' => 321, 'name' => 'Property Manager'],
        ];

        foreach ($designations as $designation) {
            Designation::updateOrCreate(
                ['hr_ref_id' => $designation['hr_ref_id']],
                ['name' => $designation['name'], 'is_active' => true],
            );
        }
    }
}
