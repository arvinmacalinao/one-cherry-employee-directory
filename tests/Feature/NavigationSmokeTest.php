<?php

namespace Tests\Feature;

use App\Livewire\Companies\Show as CompaniesShow;
use App\Models\Company;
use App\Models\Employee;
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

/**
 * There is no employee account. The public landing page is the phone directory;
 * the employee directory and every admin module are Super Admin only, and an
 * Admin can only reach the phone directory editor. See architecture-plan.md §2.4.
 */
class NavigationSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

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

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('Super Admin');
    }

    public function test_login_page_renders(): void
    {
        $this->get('/login')->assertOk()->assertSeeLivewire('auth.login');
    }

    public function test_guest_sees_the_phone_directory_but_not_the_employee_directory(): void
    {
        $employee = Employee::where('employee_id', 'EMP-00021')->firstOrFail();

        $this->get('/')->assertOk()->assertSeeLivewire('phone-directory');

        foreach (['/dashboard', '/directory', "/directory/{$employee->id}", '/companies', '/departments'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }

    public function test_super_admin_can_reach_every_employee_directory_page(): void
    {
        $employee = Employee::where('employee_id', 'EMP-00021')->firstOrFail();

        $this->actingAs($this->superAdmin);

        $this->get('/')->assertOk();
        $this->get('/dashboard')->assertOk();
        $this->get('/directory')->assertOk();
        $this->get("/directory/{$employee->id}")->assertOk();
        $this->get('/companies')->assertOk();
        $this->get('/departments')->assertOk();
    }

    public function test_admin_can_only_reach_the_phone_directory_editor(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $employee = Employee::where('employee_id', 'EMP-00021')->firstOrFail();

        $this->actingAs($admin);

        $this->get('/')->assertOk();
        $this->get('/admin/phone-directory')->assertOk()->assertDontSeeText('Employees');

        foreach ([
            '/dashboard', '/directory', "/directory/{$employee->id}", '/companies', '/departments',
            '/admin', '/admin/employees', '/admin/companies', '/admin/departments', '/admin/designations',
            '/admin/office-locations', '/admin/announcements', '/admin/sync', '/admin/settings',
            '/admin/audit-logs', '/admin/users',
        ] as $url) {
            $this->get($url)->assertForbidden();
        }
    }

    public function test_signed_in_users_are_sent_to_their_own_landing_page_from_login(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $this->actingAs($admin)->get('/login')->assertRedirect('/admin/phone-directory');
        $this->actingAs($this->superAdmin)->get('/login')->assertRedirect('/admin');
    }

    public function test_guest_cannot_reach_admin_panel(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->get('/admin/phone-directory')->assertRedirect('/login');
    }

    public function test_super_admin_can_reach_admin_panel(): void
    {
        $this->actingAs($this->superAdmin)->get('/admin')->assertOk();
        $this->actingAs($this->superAdmin)->get('/admin/phone-directory')->assertOk();
        $this->actingAs($this->superAdmin)->get('/admin/users')->assertOk();
    }

    public function test_company_show_page_renders_every_remaining_tab(): void
    {
        $company = Company::where('hr_ref_id', 102)->firstOrFail(); // Cherry Digital Solutions

        $this->actingAs($this->superAdmin);

        $this->get("/companies/{$company->id}")->assertOk();

        Livewire::test(CompaniesShow::class, ['company' => $company])
            ->assertSet('tab', 'overview')
            ->call('setTab', 'departments')->assertSet('tab', 'departments')
            ->call('setTab', 'employees')->assertSet('tab', 'employees')
            ->assertSeeText('Andrea Reyes');
    }

    public function test_departments_index_links_filter_into_directory(): void
    {
        $employee = Employee::where('employee_id', 'EMP-00021')->firstOrFail();
        $department = $employee->department;

        $this->actingAs($this->superAdmin);

        $this->get('/departments')->assertOk()->assertSeeText($department->name);
        $this->get("/directory?department={$department->id}")->assertOk()->assertSeeText($employee->full_name);
    }

    public function test_directory_can_be_sorted_by_company_or_department(): void
    {
        // Regression: sorting joins companies/departments, both of which also have
        // an is_active column — an unqualified where('is_active', ...) inside the
        // visibleInDirectory scope throws "Column 'is_active' in where clause is
        // ambiguous" once that join is added.
        $this->actingAs($this->superAdmin);
        $this->get('/directory?sort=company')->assertOk();
        $this->get('/directory?sort=department')->assertOk();
    }
}
