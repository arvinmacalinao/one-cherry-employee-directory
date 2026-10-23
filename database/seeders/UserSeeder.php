<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Staff accounts only — there is no employee account. See architecture-plan.md §2.4.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::updateOrCreate(
            ['email' => 'admin@onecherry.group'],
            ['name' => 'OCED Super Admin', 'password' => 'password', 'is_active' => true],
        );
        $superAdmin->syncRoles(['Super Admin']);

        $admin = User::updateOrCreate(
            ['email' => 'phones@onecherry.group'],
            ['name' => 'Phone Directory Admin', 'password' => 'password', 'is_active' => true],
        );
        $admin->syncRoles(['Admin']);

        $this->command?->table(
            ['Role', 'Email', 'Password'],
            [
                ['Super Admin', $superAdmin->email, 'password'],
                ['Admin', $admin->email, 'password'],
            ],
        );
    }
}
