<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Administrators only — there is no employee account. See architecture-plan.md §2.4.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@onecherry.group'],
            ['name' => 'OCED Administrator', 'password' => 'password', 'is_active' => true],
        );
        $admin->syncRoles(['Administrator']);

        $this->command?->table(
            ['Role', 'Email', 'Password'],
            [['Administrator', $admin->email, 'password']],
        );
    }
}
