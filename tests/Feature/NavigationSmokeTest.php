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
use Database\Seeders\OfficeLocationSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NavigationSmokeTest extends TestCase
{
    use RefreshDatabase;

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
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_login_page_renders(): void
    {
        $this->get('/login')->assertOk()->assertSeeLivewire('auth.login');
    }

    public function test_employee_can_reach_every_public_page(): void
    {
        $employee = Employee::where('employee_id', 'EMP-00021')->firstOrFail();
        $user = User::factory()->create(['employee_id' => $employee->id]);
        $user->assignRole('Employee');

        $this->actingAs($user);

        $this->get('/')->assertOk();
        $this->get('/directory')->assertOk();
        $this->get("/directory/{$employee->id}")->assertOk();
        $this->get('/companies')->assertOk();
        $this->get('/departments')->assertOk();
        $this->get('/favorites')->assertOk();
        $this->get('/profile')->assertRedirect("/directory/{$employee->id}");
    }

    public function test_employee_cannot_reach_admin_panel(): void
    {
        $employee = Employee::where('employee_id', 'EMP-00021')->firstOrFail();
        $user = User::factory()->create(['employee_id' => $employee->id]);
        $user->assignRole('Employee');

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_administrator_can_reach_admin_panel(): void
    {
        $employee = Employee::where('employee_id', 'EMP-00034')->firstOrFail();
        $user = User::factory()->create(['employee_id' => $employee->id]);
        $user->assignRole('Administrator');

        $this->actingAs($user)->get('/admin')->assertOk();
    }

    public function test_company_show_page_renders_every_tab(): void
    {
        $employee = Employee::where('employee_id', 'EMP-00021')->firstOrFail();
        $user = User::factory()->create(['employee_id' => $employee->id]);
        $user->assignRole('Employee');
        $this->actingAs($user);

        $company = Company::where('hr_ref_id', 102)->firstOrFail(); // Cherry Digital Solutions

        $this->get("/companies/{$company->id}")->assertOk();

        Livewire::test(CompaniesShow::class, ['company' => $company])
            ->assertSet('tab', 'overview')
            ->call('setTab', 'departments')->assertSet('tab', 'departments')
            ->call('setTab', 'employees')->assertSet('tab', 'employees')
            ->call('setTab', 'orgchart')->assertSet('tab', 'orgchart')
            ->assertSeeText('Andrea Reyes'); // department head + org chart root
    }

    public function test_departments_index_links_filter_into_directory(): void
    {
        $employee = Employee::where('employee_id', 'EMP-00021')->firstOrFail();
        $user = User::factory()->create(['employee_id' => $employee->id]);
        $user->assignRole('Employee');
        $this->actingAs($user);

        $department = $employee->department;

        $this->get('/departments')->assertOk()->assertSeeText($department->name);
        $this->get("/directory?department={$department->id}")->assertOk()->assertSeeText($employee->full_name);
    }
}
