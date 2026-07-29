<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Administrator is the only role — there is no Employee role, since there is no
 * employee account of any kind. See architecture-plan.md §2.4.
 */
class RoleSeeder extends Seeder
{
    protected array $adminPermissions = [
        'manage employees',
        'manage companies',
        'manage departments',
        'manage designations',
        'manage office locations',
        'manage announcements',
        'run hr sync',
        'manage settings',
        'view audit logs',
    ];

    public function run(): void
    {
        foreach ($this->adminPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $administrator = Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);
        $administrator->syncPermissions($this->adminPermissions);
    }
}
