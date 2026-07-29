<?php

namespace Tests\Feature;

use App\Livewire\Admin\AuditLogs\Index as AdminAuditLogs;
use App\Livewire\Admin\Settings as AdminSettings;
use App\Livewire\Admin\Sync as AdminSync;
use App\Models\Company;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Setting;
use App\Models\User;
use App\Services\HrSync\DTOs\SyncPreviewResult;
use Database\Seeders\CompanySeeder;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\DesignationSeeder;
use Database\Seeders\EmployeeSeeder;
use Database\Seeders\EmployeeStatusSeeder;
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
            EmployeeStatusSeeder::class,
            EmployeeSeeder::class,
        ]);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrator');
    }

    public function test_admin_can_generate_a_preview_that_writes_nothing(): void
    {
        $this->actingAs($this->admin);
        $this->get('/admin/sync')->assertOk();

        $employeeCountBefore = Employee::count();
        $companyCountBefore = Company::count();

        // FakeHrSource always injects a synthetic new hire and one promotion (see
        // its docblock), so the preview is never empty — but none of it should persist.
        Livewire::test(AdminSync::class)
            ->call('generatePreview')
            ->assertSet('preview', fn (?SyncPreviewResult $preview) => $preview !== null && count($preview->newEmployees) === 1);

        $this->assertSame($employeeCountBefore, Employee::count(), 'preview must never write to the database');
        $this->assertSame($companyCountBefore, Company::count(), 'preview must never auto-create lookup rows either');
        $this->assertDatabaseCount('api_sync_logs', 0);
    }

    public function test_confirming_first_sync_runs_it_and_sets_the_completion_flag(): void
    {
        $this->actingAs($this->admin);
        $this->assertNull(Setting::get('hr_first_sync_completed_at'));

        Livewire::test(AdminSync::class)
            ->call('generatePreview')
            ->call('confirmFirstSync')
            ->assertSet('flash', 'Sync complete — everything is up to date.');

        $this->assertDatabaseCount('api_sync_logs', 1);
        $this->assertNotNull(Setting::get('hr_first_sync_completed_at'));
    }

    public function test_admin_can_run_sync_and_see_history(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(AdminSync::class)
            ->call('runSync')
            ->assertSet('flash', 'Sync complete — everything is up to date.');

        $this->assertDatabaseCount('api_sync_logs', 1);
    }

    public function test_sync_flags_new_designation_names_and_admin_can_merge_a_duplicate(): void
    {
        // Simulate what HrSyncService creates when it sees a designation identity it hasn't seen before.
        $duplicate = Designation::create([
            'name' => 'Sr. Software Engineer', // near-duplicate of a real seeded name, needs merging
            'is_active' => true,
            'needs_review' => true,
        ]);
        $employee = Employee::where('employee_id', 'EMP-00021')->firstOrFail();
        $employee->update(['designation_id' => $duplicate->id]);
        $target = Designation::where('id', '!=', $duplicate->id)->firstOrFail();

        $this->actingAs($this->admin);

        Livewire::test(AdminSync::class)
            ->assertSee('New Designation: "Sr. Software Engineer"', false)
            ->call('mergeUnmapped', 'designation', $duplicate->id, $target->id)
            ->assertSet('flash', 'Designation merged and duplicate record removed.');

        $this->assertSoftDeleted('designations', ['id' => $duplicate->id]);
        $this->assertSame($target->id, $employee->fresh()->designation_id);
    }

    public function test_admin_can_mark_a_flagged_company_as_reviewed_without_merging(): void
    {
        $company = Company::create([
            'name' => 'Cherry Ventures',
            'slug' => 'cherry-ventures',
            'is_active' => true,
            'needs_review' => true,
        ]);

        $this->actingAs($this->admin);

        Livewire::test(AdminSync::class)
            ->assertSee('New Company: "Cherry Ventures"', false)
            ->call('markReviewed', 'company', $company->id)
            ->assertSet('flash', 'Company marked as reviewed.');

        $this->assertFalse($company->fresh()->needs_review);
    }

    public function test_admin_can_bulk_mark_all_flagged_records_of_one_type_as_reviewed(): void
    {
        foreach (range(1, 3) as $i) {
            Company::create(['name' => "New Company {$i}", 'slug' => "new-company-{$i}", 'is_active' => true, 'needs_review' => true]);
        }
        Designation::create(['name' => 'Should Stay Flagged', 'is_active' => true, 'needs_review' => true]);

        $this->actingAs($this->admin);

        Livewire::test(AdminSync::class)
            ->call('markAllReviewed', 'company')
            ->assertSet('flash', 'Marked 3 records as reviewed.');

        $this->assertSame(0, Company::needsReview()->count());
        $this->assertSame(1, Designation::needsReview()->count(), 'bulk-marking one type must not touch another');
    }

    public function test_admin_can_bulk_mark_every_flagged_record_as_reviewed_regardless_of_type(): void
    {
        foreach (range(1, 2) as $i) {
            Company::create(['name' => "Another Company {$i}", 'slug' => "another-company-{$i}", 'is_active' => true, 'needs_review' => true]);
        }
        Designation::create(['name' => 'New Designation', 'is_active' => true, 'needs_review' => true]);

        $this->actingAs($this->admin);

        Livewire::test(AdminSync::class)
            ->call('markAllReviewed')
            ->assertSet('flash', 'Marked 3 records as reviewed.');

        $this->assertSame(0, Company::needsReview()->count());
        $this->assertSame(0, Designation::needsReview()->count());
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
