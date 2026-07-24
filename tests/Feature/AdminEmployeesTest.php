<?php

namespace Tests\Feature;

use App\Livewire\Admin\Employees\Index as AdminEmployees;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
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

class AdminEmployeesTest extends TestCase
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

    public function test_admin_can_create_a_manual_employee(): void
    {
        $this->actingAs($this->admin);
        $this->get('/admin/employees')->assertOk();

        $company = Company::whereNotNull('hr_ref_id')->firstOrFail();
        $department = Department::where('company_id', $company->id)->firstOrFail();
        $designation = Designation::where('company_id', $company->id)->firstOrFail();

        Livewire::test(AdminEmployees::class)
            ->call('openCreate')
            ->set('form.first_name', 'Carla')
            ->set('form.last_name', 'Jimenez')
            ->set('form.email', 'carla.jimenez@cherryrealty.onecherry.group')
            ->set('form.company_id', (string) $company->id)
            ->set('form.department_id', (string) $department->id)
            ->set('form.designation_id', (string) $designation->id)
            ->set('form.mobile_number', '+63 917 000 1111')
            ->set('form.skills', 'Contracts, Compliance')
            ->call('save')
            ->assertHasNoErrors();

        $employee = Employee::where('email', 'carla.jimenez@cherryrealty.onecherry.group')->firstOrFail();
        $this->assertSame('manual', $employee->source->value);
        $this->assertStringStartsWith('MAN-', $employee->employee_id);
        $this->assertSame('+63 917 000 1111', $employee->profile->mobile_number);
        $this->assertEqualsCanonicalizing(['Contracts', 'Compliance'], $employee->skills->pluck('name')->all());
    }

    public function test_hr_synced_employee_org_fields_are_locked_but_profile_fields_editable(): void
    {
        $this->actingAs($this->admin);
        $employee = Employee::where('employee_id', 'EMP-00021')->firstOrFail(); // Andrea Reyes, hr_sync
        $originalDept = $employee->department_id;

        Livewire::test(AdminEmployees::class)
            ->call('openEdit', $employee->id)
            ->assertSet('isSynced', true)
            ->set('form.department_id', 999999) // should be ignored since locked
            ->set('form.office_seat', 'BGC-5F-002')
            ->set('form.about_me', 'Updated bio via admin.')
            ->call('save')
            ->assertHasNoErrors();

        $employee->refresh();
        $this->assertSame($originalDept, $employee->department_id, 'HR-controlled department must not change');
        $this->assertSame('BGC-5F-002', $employee->profile->office_seat);
        $this->assertSame('Updated bio via admin.', $employee->profile->about_me);
    }

    public function test_manual_employee_org_fields_can_be_edited(): void
    {
        $this->actingAs($this->admin);
        // Cherry Realty & Development has two seeded departments (Legal & Compliance, Property Management).
        $company = Company::where('hr_ref_id', 103)->firstOrFail();
        $departments = Department::where('company_id', $company->id)->get();
        $this->assertGreaterThanOrEqual(2, $departments->count());
        [$department, $otherDept] = $departments->all();
        $designation = Designation::where('company_id', $company->id)->firstOrFail();

        Livewire::test(AdminEmployees::class)
            ->call('openCreate')
            ->set('form.first_name', 'Test')
            ->set('form.last_name', 'Contractor')
            ->set('form.email', 'test.contractor@onecherry.group')
            ->set('form.company_id', (string) $company->id)
            ->set('form.department_id', (string) $department->id)
            ->set('form.designation_id', (string) $designation->id)
            ->call('save');

        $employee = Employee::where('email', 'test.contractor@onecherry.group')->firstOrFail();

        Livewire::test(AdminEmployees::class)
            ->call('openEdit', $employee->id)
            ->assertSet('isSynced', false)
            ->set('form.department_id', (string) $otherDept->id)
            ->call('save');

        $this->assertSame($otherDept->id, $employee->fresh()->department_id);
    }
}
