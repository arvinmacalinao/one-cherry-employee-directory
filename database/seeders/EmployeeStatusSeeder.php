<?php

namespace Database\Seeders;

use App\Models\EmployeeStatus;
use Illuminate\Database\Seeder;

/**
 * Placeholder es_id -> name values, standing in for HR's real `employee_statuses`
 * table until that's provided via SQL export — see architecture-plan.md §2.5, §10.
 * In production this table is otherwise self-populating from the HR feed and
 * needs no seeder at all.
 */
class EmployeeStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['hr_ref_id' => 1, 'name' => 'Regular'],
            ['hr_ref_id' => 2, 'name' => 'Probationary'],
            ['hr_ref_id' => 3, 'name' => 'On Leave'],
            ['hr_ref_id' => 4, 'name' => 'Resigned'],
        ];

        foreach ($statuses as $status) {
            EmployeeStatus::updateOrCreate(['hr_ref_id' => $status['hr_ref_id']], $status);
        }
    }
}
