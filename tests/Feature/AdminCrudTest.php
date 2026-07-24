<?php

namespace Tests\Feature;

use App\Livewire\Admin\Companies\Index as AdminCompanies;
use App\Livewire\Admin\Departments\Index as AdminDepartments;
use App\Livewire\Admin\Designations\Index as AdminDesignations;
use App\Livewire\Admin\Offices\Index as AdminOffices;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\OfficeLocation;
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
            EmployeeSeeder::class,
        ]);

        $employee = Employee::where('employee_id', 'EMP-00034')->firstOrFail();
        $this->admin = User::factory()->create(['employee_id' => $employee->id]);
        $this->admin->assignRole('Administrator');
    }

    public function test_non_admin_cannot_reach_any_admin_crud_page(): void
    {
        $employee = Employee::where('employee_id', 'EMP-00021')->firstOrFail();
        $user = User::factory()->create(['employee_id' => $employee->id]);
        $user->assignRole('Employee');

        $this->actingAs($user);
        $this->get('/admin/companies')->assertForbidden();
        $this->get('/admin/departments')->assertForbidden();
        $this->get('/admin/designations')->assertForbidden();
        $this->get('/admin/office-locations')->assertForbidden();
    }

    public function test_admin_can_create_and_edit_a_company(): void
    {
        $this->actingAs($this->admin);
        $this->get('/admin/companies')->assertOk();

        Livewire::test(AdminCompanies::class)
            ->call('openCreate')
            ->set('form.name', 'Cherry Ventures')
            ->set('form.email', 'info@cherryventures.onecherry.group')
            ->set('form.color_theme', '#123456')
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
        $company = Company::whereNotNull('hr_ref_id')->firstOrFail();

        Livewire::test(AdminDepartments::class)
            ->call('openCreate')
            ->set('form.name', 'Data & Analytics')
            ->set('form.company_id', (string) $company->id)
            ->call('save')
            ->assertHasNoErrors();

        $department = Department::where('name', 'Data & Analytics')->firstOrFail();
        $this->assertSame($company->id, $department->company_id);

        Livewire::test(AdminDepartments::class)
            ->call('openEdit', $department->id)
            ->set('form.description', 'Group-wide reporting and analytics.')
            ->call('save');

        $this->assertSame('Group-wide reporting and analytics.', $department->fresh()->description);
    }

    public function test_admin_can_create_and_edit_a_designation(): void
    {
        $this->actingAs($this->admin);
        $this->get('/admin/designations')->assertOk();
        $company = Company::whereNotNull('hr_ref_id')->firstOrFail();

        Livewire::test(AdminDesignations::class)
            ->call('openCreate')
            ->set('form.name', 'Data Analyst')
            ->set('form.company_id', (string) $company->id)
            ->set('form.hierarchy_level', 3)
            ->call('save')
            ->assertHasNoErrors();

        $designation = Designation::where('name', 'Data Analyst')->firstOrFail();
        $this->assertSame(3, $designation->hierarchy_level);
    }

    public function test_admin_can_create_and_edit_an_office_location(): void
    {
        $this->actingAs($this->admin);
        $this->get('/admin/office-locations')->assertOk();

        Livewire::test(AdminOffices::class)
            ->call('openCreate')
            ->set('form.name', 'Iloilo Branch')
            ->set('form.city', 'Iloilo City')
            ->call('save')
            ->assertHasNoErrors();

        $office = OfficeLocation::where('name', 'Iloilo Branch')->firstOrFail();
        $this->assertSame('Iloilo City', $office->city);
        $this->assertNull($office->company_id);
    }
}
