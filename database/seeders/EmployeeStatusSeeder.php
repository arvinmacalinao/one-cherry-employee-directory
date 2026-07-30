<?php

namespace Database\Seeders;

use App\Models\EmployeeStatus;
use Illuminate\Database\Seeder;

/**
 * Confirmed against the real HR API response (previously a placeholder guess —
 * that guess turned out wrong across all four values and, due to a since-fixed
 * bug in HrSyncService::resolveEmployeeStatus() not syncing a renamed status,
 * mislabeled 573 real "Regular" employees as "On Leave" in production. See
 * architecture-plan.md §2.5, §10.
 *
 * In production this table is otherwise self-populating from the HR feed and
 * needs no seeder at all — this only exists for local dev/demo without a real
 * HR connection.
 */
class EmployeeStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['hr_ref_id' => 1, 'name' => 'Probationary'],
            ['hr_ref_id' => 2, 'name' => 'Fixed-term'],
            ['hr_ref_id' => 3, 'name' => 'Regular'],
            ['hr_ref_id' => 4, 'name' => 'Project-based'],
        ];

        foreach ($statuses as $status) {
            EmployeeStatus::updateOrCreate(['hr_ref_id' => $status['hr_ref_id']], $status);
        }
    }
}
