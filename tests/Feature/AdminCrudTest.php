<?php

namespace Tests\Feature;

use App\Livewire\Admin\Announcements\Index as AdminAnnouncements;
use App\Livewire\Admin\Companies\Index as AdminCompanies;
use App\Livewire\Admin\Departments\Index as AdminDepartments;
use App\Livewire\Admin\Designations\Index as AdminDesignations;
use App\Livewire\Admin\Offices\Index as AdminOffices;
use App\Models\Announcement;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\OfficeLocation;
use App\Models\User;
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

class AdminCrudTest extends TestCase
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

    public function test_guest_cannot_reach_any_admin_crud_page(): void
    {
        $this->get('/admin/companies')->assertRedirect('/login');
        $this->get('/admin/departments')->assertRedirect('/login');
        $this->get('/admin/designations')->assertRedirect('/login');
        $this->get('/admin/office-locations')->assertRedirect('/login');
    }

    public function test_admin_can_create_and_edit_a_company(): void
    {
        $this->actingAs($this->admin);
        $this->get('/admin/companies')->assertOk();

        Livewire::test(AdminCompanies::class)
            ->call('openCreate')
            ->set('form.name', 'Cherry Ventures')
            ->set('form.email', 'info@cherryventures.onecherry.group')
            ->call('save')
            ->assertHasNoErrors();

        $company = Company::where('name', 'Cherry Ventures')->firstOrFail();
        $this->assertSame('info@cherryventures.onecherry.group', $company->email);
        $this->assertNull($company->hr_ref_id);

        Livewire::test(AdminCompanies::class)
            ->call('openEdit', $company->id)
            ->set('form.phone', '+63 2 0000 0000')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('+63 2 0000 0000', $company->fresh()->phone);
    }

    public function test_hr_synced_company_name_cannot_be_changed_via_form(): void
    {
        $this->actingAs($this->admin);
        $company = Company::whereNotNull('hr_ref_id')->firstOrFail();
        $originalName = $company->name;

        Livewire::test(AdminCompanies::class)
            ->call('openEdit', $company->id)
            ->assertSet('form.name', $originalName)
            ->set('form.name', 'Should Not Stick')
            ->set('form.phone', '+63 2 1111 1111')
            ->call('save');

        $company->refresh();
        $this->assertSame($originalName, $company->name);
        $this->assertSame('+63 2 1111 1111', $company->phone);
    }

    public function test_admin_can_create_and_edit_a_department(): void
    {
        $this->actingAs($this->admin);
        $this->get('/admin/departments')->assertOk();

        Livewire::test(AdminDepartments::class)
            ->call('openCreate')
            ->set('form.name', 'Data & Analytics')
            ->call('save')
            ->assertHasNoErrors();

        $department = Department::where('name', 'Data & Analytics')->firstOrFail();
        $this->assertTrue($department->is_active);
    }

    public function test_hr_synced_department_name_cannot_be_changed_via_form(): void
    {
        $this->actingAs($this->admin);
        $department = Department::whereNotNull('hr_ref_id')->firstOrFail();
        $originalName = $department->name;

        Livewire::test(AdminDepartments::class)
            ->call('openEdit', $department->id)
            ->set('form.name', 'Should Not Stick')
            ->call('save');

        $this->assertSame($originalName, $department->fresh()->name);
    }

    public function test_admin_can_create_and_edit_a_designation(): void
    {
        $this->actingAs($this->admin);
        $this->get('/admin/designations')->assertOk();

        Livewire::test(AdminDesignations::class)
            ->call('openCreate')
            ->set('form.name', 'Data Analyst')
            ->call('save')
            ->assertHasNoErrors();

        $designation = Designation::where('name', 'Data Analyst')->firstOrFail();
        $this->assertTrue($designation->is_active);
    }

    public function test_admin_can_create_and_edit_an_office_location(): void
    {
        $this->actingAs($this->admin);
        $this->get('/admin/office-locations')->assertOk();

        Livewire::test(AdminOffices::class)
            ->call('openCreate')
            ->set('form.name', 'Iloilo Branch')
            ->set('form.address', 'Iloilo City')
            ->call('save')
            ->assertHasNoErrors();

        $office = OfficeLocation::where('name', 'Iloilo Branch')->firstOrFail();
        $this->assertSame('Iloilo City', $office->address);
        $this->assertNull($office->company_id);
    }

    public function test_admin_dashboard_renders(): void
    {
        $this->actingAs($this->admin)->get('/admin')->assertOk();
    }

    public function test_admin_can_create_and_edit_an_announcement(): void
    {
        $this->actingAs($this->admin);
        $this->get('/admin/announcements')->assertOk();

        Livewire::test(AdminAnnouncements::class)
            ->call('openCreate')
            ->set('form.title', 'Founders Day')
            ->set('form.body', 'RSVP through your department head.')
            ->call('save')
            ->assertHasNoErrors();

        $announcement = Announcement::where('title', 'Founders Day')->firstOrFail();
        $this->assertSame($this->admin->id, $announcement->created_by);

        // Expiry is a display filter, not a deletion — an expired row still shows
        // in /admin/announcements, it just drops out of Home. See architecture-plan.md §3.2.
        $announcement->update(['published_at' => now()->subDays(5), 'expires_at' => now()->subDay()]);
        $this->assertTrue($announcement->fresh()->is_expired);

        $this->get('/admin/announcements')->assertOk()->assertSeeText('Founders Day');
        $this->get('/')->assertOk()->assertDontSeeText('Founders Day');
    }
}
