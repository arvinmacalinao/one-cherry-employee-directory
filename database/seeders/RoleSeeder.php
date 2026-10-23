<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Super Admin owns every module (the employee directory included — it is no
 * longer public). Admin can only edit the public phone directory. There is still
 * no employee account of any kind. See architecture-plan.md §2.4.
 */
class RoleSeeder extends Seeder
{
    protected array $superAdminPermissions = [
        'view employee directory',
        'manage employees',
        'manage companies',
        'manage departments',
        'manage designations',
        'manage office locations',
        'manage announcements',
        'run hr sync',
        'manage settings',
        'view audit logs',
        'manage users',
        'manage phone directory',
    ];

    protected array $adminPermissions = [
        'manage phone directory',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->superAdminPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web'])
            ->syncPermissions($this->superAdminPermissions);

        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web'])
            ->syncPermissions($this->adminPermissions);
    }
}
