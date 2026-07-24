<?php

namespace Tests\Feature;

use App\Livewire\Admin\AuditLogs\Index as AdminAuditLogs;
use App\Livewire\Admin\Settings as AdminSettings;
use App\Livewire\Admin\Sync as AdminSync;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\CompanySeeder;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\DesignationSeeder;
use Database\Seeders\EmployeeSeeder;
use Database\Seeders\OfficeLocationSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminSyncSettingsAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            CompanySeeder::class,
            DesignationSeeder::class,
            DepartmentSeeder::class,
            OfficeLocationSeeder::class,
            EmployeeSeeder::class,
        ]);

        $employee = Employee::where('employee_id', 'EMP-00034')->firstOrFail();
        $this->admin = User::factory()->create(['employee_id' => $employee->id]);
        $this->admin->assignRole('Administrator');
    }

    public function test_admin_can_run_sync_and_see_history(): void
    {
        $this->actingAs($this->admin);
        $this->get('/admin/sync')->assertOk();

        Livewire::test(AdminSync::class)
            ->call('runSync')
            ->assertSet('flash', 'Sync complete — everything is up to date.');

        $this->assertDatabaseCount('api_sync_logs', 1);
    }

    public function test_sync_creates_unmapped_stubs_and_admin_can_merge_them(): void
    {
        // Simulate what HrSyncService would create when it can't resolve a designation id.
        $company = Company::where('hr_ref_id', 102)->firstOrFail();
        $stub = Designation::create([
            'hr_ref_id' => 9999,
            'company_id' => $company->id,
            'name' => 'Unmapped Designation #9999',
            'hierarchy_level' => 1,
            'is_active' => true,
        ]);
        $employee = Employee::where('employee_id', 'EMP-00021')->firstOrFail();
        $employee->update(['designation_id' => $stub->id]);
        $target = Designation::where('company_id', $company->id)->where('id', '!=', $stub->id)->firstOrFail();

        $this->actingAs($this->admin);

        Livewire::test(AdminSync::class)
            ->assertSee('Unmapped Designation #9999')
            ->call('mergeUnmapped', 'designation', $stub->id, $target->id)
            ->assertSet('flash', 'Designation merged and stub record removed.');

        $this->assertSoftDeleted('designations', ['id' => $stub->id]);
        $this->assertSame($target->id, $employee->fresh()->designation_id);
    }

    public function test_admin_can_save_settings_and_it_affects_the_scheduler_source(): void
    {
        $this->actingAs($this->admin);
        $this->get('/admin/settings')->assertOk();

        Livewire::test(AdminSettings::class)
            ->set('app_name', 'One Cherry Directory (Staging)')
            ->set('hr_sync_schedule', 'nightly')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('One Cherry Directory (Staging)', Setting::get('app_name'));
        $this->assertSame('nightly', Setting::get('hr_sync_schedule'));
    }

    public function test_admin_can_view_audit_logs_after_an_edit(): void
    {
        // owen-it/laravel-auditing suppresses events while runningInConsole() is true,
        // which covers the entire `php artisan test` process — flip it on to verify
        // the audit trail actually gets written for a real edit.
        config(['audit.console' => true]);

        $this->actingAs($this->admin);
        $employee = Employee::where('employee_id', 'EMP-00021')->firstOrFail();
        $employee->update(['first_name' => 'Andrea-Updated']);

        $this->assertDatabaseHas('audits', ['auditable_type' => Employee::class, 'auditable_id' => $employee->id, 'event' => 'updated']);

        $this->get('/admin/audit-logs')->assertOk();

        Livewire::test(AdminAuditLogs::class)
            ->assertSeeText('Employee');
    }
}
