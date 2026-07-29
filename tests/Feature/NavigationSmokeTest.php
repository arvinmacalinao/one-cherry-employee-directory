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
 * There is no employee account — the directory is open to anyone on the network,
 * unauthenticated. Only /admin requires a session. See architecture-plan.md §2.4.
 */
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
            EmployeeStatusSeeder::class,
            EmployeeSeeder::class,
        ]);
    }

    public function test_login_page_renders(): void
    {
        $this->get('/login')->assertOk()->assertSeeLivewire('auth.login');
    }

    public function test_anyone_can_reach_every_public_page_without_authenticating(): void
    {
        $employee = Employee::where('employee_id', 'EMP-00021')->firstOrFail();

        $this->get('/')->assertOk();
        $this->get('/directory')->assertOk();
        $this->get("/directory/{$employee->id}")->assertOk();
        $this->get('/companies')->assertOk();
        $this->get('/departments')->assertOk();
    }

    public function test_guest_cannot_reach_admin_panel(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_administrator_can_reach_admin_panel(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Administrator');

        $this->actingAs($user)->get('/admin')->assertOk();
    }

    public function test_company_show_page_renders_every_remaining_tab(): void
    {
        $company = Company::where('hr_ref_id', 102)->firstOrFail(); // Cherry Digital Solutions

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

        $this->get('/departments')->assertOk()->assertSeeText($department->name);
        $this->get("/directory?department={$department->id}")->assertOk()->assertSeeText($employee->full_name);
    }
}
