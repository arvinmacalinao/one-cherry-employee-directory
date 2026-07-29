<?php

namespace Tests\Feature;

use App\Livewire\Admin\Employees\Index as AdminEmployees;
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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Every employee row originates from HR sync — there is no manual "Add Employee"
 * flow anymore. An Admin may only edit directory-owned fields; HR-owned fields
 * are read-only. See architecture-plan.md §2.4, §5, §7.
 */
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
            EmployeeStatusSeeder::class,
            EmployeeSeeder::class,
        ]);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Administrator');
    }

    public function test_admin_can_edit_only_directory_owned_fields(): void
    {
        $this->actingAs($this->admin);
        $this->get('/admin/employees')->assertOk();

        $employee = Employee::where('employee_id', 'EMP-00021')->firstOrFail(); // Andrea Reyes
        $originalFirstName = $employee->first_name;
        $originalCompanyId = $employee->company_id;
        $originalDepartmentId = $employee->department_id;

        Livewire::test(AdminEmployees::class)
            ->call('openEdit', $employee->id)
            ->set('form.viber_number', '+63 917 000 1111')
            ->set('form.about_me', 'Updated bio via admin.')
            ->set('form.birthday', '1990-05-12')
            ->call('save')
            ->assertHasNoErrors();

        $employee->refresh();
        $this->assertSame($originalFirstName, $employee->first_name, 'HR-owned first name must be untouched by the admin form');
        $this->assertSame($originalCompanyId, $employee->company_id, 'HR-owned company must be untouched by the admin form');
        $this->assertSame($originalDepartmentId, $employee->department_id, 'HR-owned department must be untouched by the admin form');
        $this->assertSame('+63 917 000 1111', $employee->profile->viber_number);
        $this->assertSame('Updated bio via admin.', $employee->profile->about_me);
        $this->assertSame('1990-05-12', $employee->profile->birthday->format('Y-m-d'));
    }

    public function test_admin_form_has_no_way_to_create_a_new_employee(): void
    {
        $this->actingAs($this->admin);

        $this->assertFalse(method_exists(AdminEmployees::class, 'openCreate'), 'employees are HR-sync-only — there is no manual creation path');
    }

    public function test_admin_can_upload_and_remove_an_employee_photo(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin);
        $employee = Employee::where('employee_id', 'EMP-00021')->firstOrFail();

        Livewire::test(AdminEmployees::class)
            ->call('openEdit', $employee->id)
            ->set('photo', UploadedFile::fake()->image('headshot.jpg'))
            ->call('save')
            ->assertHasNoErrors();

        $employee->refresh();
        $this->assertTrue($employee->hasMedia('photo'));
        $this->assertNotEmpty($employee->getFirstMediaUrl('photo', 'thumb'));

        Livewire::test(AdminEmployees::class)
            ->call('openEdit', $employee->id)
            ->call('removePhoto');

        $this->assertFalse($employee->fresh()->hasMedia('photo'));
    }
}
