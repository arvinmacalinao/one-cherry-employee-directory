<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    protected array $adminPermissions = [
        'manage employees',
        'manage companies',
        'manage departments',
        'manage designations',
        'manage office locations',
        'run hr sync',
        'manage settings',
        'view audit logs',
    ];

    public function run(): void
    {
        foreach ($this->adminPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $employee = Role::firstOrCreate(['name' => 'Employee', 'guard_name' => 'web']);

        $administrator = Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);
        $administrator->syncPermissions($this->adminPermissions);
    }
}
