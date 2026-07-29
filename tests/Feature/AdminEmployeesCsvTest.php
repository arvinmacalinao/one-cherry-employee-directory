<?php

namespace Tests\Feature;

use App\Livewire\Admin\Employees\Index as AdminEmployees;
use App\Models\Employee;
use App\Models\OfficeLocation;
use App\Models\User;
use App\Services\EmployeeCsvService;
use Database\Seeders\CompanySeeder;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\DesignationSeeder;
use Database\Seeders\EmployeeSeeder;
use Database\Seeders\EmployeeStatusSeeder;
use Database\Seeders\OfficeLocationSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Round-trip bulk editor: Export CSV -> fill in offline -> Import CSV. See
 * EmployeeCsvService and architecture-plan.md §2.5 (email is fallback-editable
 * precisely because HR usually doesn't send one).
 */
class AdminEmployeesCsvTest extends TestCase
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

    protected function csvUpload(string $content): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('employees.csv', $content);
    }

    public function test_export_produces_a_row_per_employee_with_expected_headers(): void
    {
        $employee = Employee::where('employee_id', 'EMP-00021')->firstOrFail();

        $rows = app(EmployeeCsvService::class)->exportRows();

        $this->assertSame(EmployeeCsvService::HEADERS, array_keys($rows->first()));

        $row = $rows->firstWhere('employee_id', $employee->employee_id);
        $this->assertNotNull($row);
        $this->assertSame($employee->first_name, $row['first_name']);
        $this->assertSame($employee->company->name, $row['company']);
    }

    public function test_export_button_triggers_a_real_file_download(): void
    {
        $this->actingAs($this->admin);

        $download = Livewire::test(AdminEmployees::class)->call('exportCsv')->effects['download'] ?? null;

        $this->assertNotNull($download, 'exportCsv must return a real file-download response');
        $this->assertStringEndsWith('.csv', $download['name']);

        $content = base64_decode($download['content']);
        $this->assertStringContainsString(implode(',', EmployeeCsvService::HEADERS), $content);
        $this->assertStringContainsString('EMP-00021', $content);
    }

    public function test_import_fills_in_email_and_telephone_leaving_blank_columns_untouched(): void
    {
        $employee = Employee::where('employee_id', 'EMP-00021')->firstOrFail();
        $employee->update(['email' => null]);
        $originalViber = $employee->profile->viber_number;

        $csv = "employee_id,first_name,last_name,company,department,designation,email,viber_number,telephone,local_extension,office_location,birthday,about_me\n"
            .$employee->employee_id.',Should,Ignore,Should Ignore,Should Ignore,Should Ignore,andrea.reyes@onecherry.group,,(02) 8888 1200,2231,,,'."\n";

        $this->actingAs($this->admin);

        Livewire::test(AdminEmployees::class)
            ->set('importFile', $this->csvUpload($csv))
            ->call('runImport')
            ->assertSet('importSummary.rowsProcessed', 1)
            ->assertSet('importSummary.employeesUpdated', 1);

        $employee->refresh();
        $this->assertSame('andrea.reyes@onecherry.group', $employee->email);
        $this->assertSame('(02) 8888 1200', $employee->profile->telephone);
        $this->assertSame('2231', $employee->profile->local_extension);
        $this->assertSame($originalViber, $employee->profile->viber_number, 'blank cell must not clear an existing value');
        $this->assertNotSame('Should', $employee->first_name, 'HR-owned columns present in the file must never be applied');
    }

    public function test_import_reports_a_warning_for_an_unknown_employee_id_without_failing_the_batch(): void
    {
        $csv = "employee_id,first_name,last_name,company,department,designation,email,viber_number,telephone,local_extension,office_location,birthday,about_me\n"
            ."DOES-NOT-EXIST,,,,,,someone@onecherry.group,,,,,,\n";

        $this->actingAs($this->admin);

        Livewire::test(AdminEmployees::class)
            ->set('importFile', $this->csvUpload($csv))
            ->call('runImport')
            ->assertSet('importSummary.employeesUpdated', 0);

        $this->assertDatabaseMissing('employees', ['email' => 'someone@onecherry.group']);
    }

    public function test_import_skips_a_duplicate_email_with_a_warning(): void
    {
        $target = Employee::where('employee_id', 'EMP-00021')->firstOrFail();
        $takenEmail = Employee::where('employee_id', 'EMP-00034')->firstOrFail()->email;

        $csv = "employee_id,first_name,last_name,company,department,designation,email,viber_number,telephone,local_extension,office_location,birthday,about_me\n"
            .$target->employee_id.",,,,,,{$takenEmail},,,,,,\n";

        $this->actingAs($this->admin);

        Livewire::test(AdminEmployees::class)
            ->set('importFile', $this->csvUpload($csv))
            ->call('runImport');

        $this->assertNotSame($takenEmail, $target->fresh()->email);
    }

    public function test_import_matches_office_location_by_name(): void
    {
        $employee = Employee::where('employee_id', 'EMP-00021')->firstOrFail();
        $office = OfficeLocation::where('name', 'Makati HQ')->firstOrFail();

        $csv = "employee_id,first_name,last_name,company,department,designation,email,viber_number,telephone,local_extension,office_location,birthday,about_me\n"
            .$employee->employee_id.',,,,,,,,,,Makati HQ,,'."\n";

        $this->actingAs($this->admin);

        Livewire::test(AdminEmployees::class)
            ->set('importFile', $this->csvUpload($csv))
            ->call('runImport');

        $this->assertSame($office->id, $employee->fresh()->profile->office_location_id);
    }
}
