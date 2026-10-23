<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * The old single "Administrator" role becomes "Super Admin" (every existing
 * account keeps full access), and a new, narrow "Admin" role is added that can
 * only edit the public phone directory. Kept in a migration rather than only the
 * RoleSeeder so production databases pick it up on deploy.
 */
return new class extends Migration
{
    protected array $newPermissions = [
        'view employee directory',
        'manage phone directory',
        'manage users',
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::where('name', 'Administrator')->where('guard_name', 'web')->update(['name' => 'Super Admin']);

        foreach ($this->newPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        if ($superAdmin = Role::where('name', 'Super Admin')->where('guard_name', 'web')->first()) {
            $superAdmin->givePermissionTo($this->newPermissions);
        }

        Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web'])
            ->syncPermissions(['manage phone directory']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::where('name', 'Admin')->where('guard_name', 'web')->first()?->delete();
        Permission::whereIn('name', $this->newPermissions)->where('guard_name', 'web')->get()->each->delete();
        Role::where('name', 'Super Admin')->where('guard_name', 'web')->update(['name' => 'Administrator']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
