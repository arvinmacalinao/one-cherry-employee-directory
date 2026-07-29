<?php

namespace Tests\Feature;

use App\Enums\SyncType;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\EmployeeStatus;
use App\Services\HrSync\Contracts\HrSourceInterface;
use App\Services\HrSync\Exceptions\HrApiException;
use App\Services\HrSync\HrRestApiSource;
use App\Services\HrSync\HrSyncService;
use Database\Seeders\CompanySeeder;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\DesignationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Verifies HrRestApiSource against the real, confirmed contract (architecture-plan.md §2.5):
 *   GET {base}/api/employees -> plain JSON array (no pagination wrapper), filtered
 *   server-side to u_active=1, records shaped with nested {id, name} objects for
 *   company/department/designation/employment_status plus discrete first/middle/last name.
 */
class HrRestApiSourceTest extends TestCase
{
    use RefreshDatabase;

    protected function source(): HrRestApiSource
    {
        return new HrRestApiSource(
            baseUrl: 'https://hr.example.test',
            apiKey: '1|test-fake-token-not-a-real-credential',
            endpoint: '/api/employees',
            timeout: 5,
        );
    }

    protected function record(array $overrides = []): array
    {
        return array_merge([
            'employee_id' => 'EMP-100',
            'first_name' => 'Ramon',
            'middle_name' => null,
            'last_name' => 'Sy',
            'name' => 'Ramon Sy',
            'username' => 'ramon.sy',
            'email' => 'ramon.sy@onecherry.group',
            'company' => ['id' => null, 'name' => 'Cherry Digital Solutions'],
            'department' => ['id' => null, 'name' => 'Engineering'],
            'designation' => ['id' => null, 'name' => 'Software Engineer'],
            'supervisor' => ['id' => null, 'employee_id' => null, 'name' => null],
            'employment_status' => ['id' => 1, 'name' => 'Regular'],
            'job_level' => ['id' => null, 'name' => null],
            'date_hired' => '2024-01-15',
            'date_regularized' => '2024-04-15',
            'date_separated' => null,
        ], $overrides);
    }

    public function test_fetches_and_maps_a_plain_json_array(): void
    {
        Http::fake([
            'hr.example.test/api/employees' => Http::response([$this->record()]),
        ]);

        $results = $this->source()->fetchEmployees();

        $this->assertCount(1, $results);
        $dto = $results->first();
        $this->assertSame('EMP-100', $dto->employeeCode);
        $this->assertSame('Ramon', $dto->firstName);
        $this->assertSame('Sy', $dto->lastName);
        $this->assertSame('ramon.sy', $dto->username);
        $this->assertSame('ramon.sy@onecherry.group', $dto->email);
        $this->assertSame('Cherry Digital Solutions', $dto->companyName);
        $this->assertNull($dto->companyId);
        $this->assertSame('Engineering', $dto->departmentName);
        $this->assertSame('Software Engineer', $dto->designationName);
        $this->assertSame(1, $dto->employmentStatusId);
        $this->assertSame('Regular', $dto->employmentStatusName);
        $this->assertSame('2024-01-15', $dto->dateHired);

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer 1|test-fake-token-not-a-real-credential'));
    }

    public function test_maps_ids_and_supervisor_when_hr_sends_them(): void
    {
        Http::fake([
            'hr.example.test/api/employees' => Http::response([
                $this->record([
                    'company' => ['id' => 12, 'name' => 'Cherry Digital Solutions'],
                    'department' => ['id' => 22, 'name' => 'Engineering'],
                    'designation' => ['id' => 34, 'name' => 'Software Engineer'],
                    'supervisor' => ['id' => 5, 'employee_id' => 'EMP-001', 'name' => 'Boss Person'],
                ]),
            ]),
        ]);

        $dto = $this->source()->fetchEmployees()->first();

        $this->assertSame(12, $dto->companyId);
        $this->assertSame(22, $dto->departmentId);
        $this->assertSame(34, $dto->designationId);
        $this->assertSame('EMP-001', $dto->supervisorEmployeeCode);
    }

    public function test_discrete_name_fields_are_used_directly_no_splitting(): void
    {
        Http::fake([
            'hr.example.test/api/employees' => Http::response([
                $this->record(['first_name' => 'Maria Clara', 'middle_name' => 'Dela', 'last_name' => 'Cruz']),
            ]),
        ]);

        $dto = $this->source()->fetchEmployees()->first();

        $this->assertSame('Maria Clara', $dto->firstName);
        $this->assertSame('Dela', $dto->middleName);
        $this->assertSame('Cruz', $dto->lastName);
    }

    public function test_skips_records_missing_required_fields_without_failing_the_batch(): void
    {
        Http::fake([
            'hr.example.test/api/employees' => Http::response([
                $this->record(['employee_id' => 'EMP-100']),
                $this->record(['employee_id' => 'EMP-101', 'last_name' => null]),
            ]),
        ]);

        $results = $this->source()->fetchEmployees();

        $this->assertCount(1, $results);
        $this->assertSame('EMP-100', $results->first()->employeeCode);
    }

    public function test_throws_on_http_error(): void
    {
        Http::fake([
            'hr.example.test/api/employees' => Http::response(['message' => 'Unauthorized'], 401),
        ]);

        $this->expectException(HrApiException::class);

        $this->source()->fetchEmployees();
    }

    public function test_end_to_end_sync_matches_company_department_designation_by_name(): void
    {
        $this->seed([CompanySeeder::class, DesignationSeeder::class, DepartmentSeeder::class]);

        $company = Company::where('name', 'Cherry Digital Solutions')->firstOrFail();
        $department = Department::where('name', 'Engineering')->where('company_id', $company->id)->firstOrFail();
        $designation = Designation::where('name', 'Software Engineer')->where('company_id', $company->id)->firstOrFail();

        Http::fake([
            'hr.example.test/api/employees' => Http::response([$this->record(['employee_id' => 'EMP-900'])]),
        ]);

        $this->app->bind(HrSourceInterface::class, fn () => $this->source());

        $log = app(HrSyncService::class)->sync(SyncType::Manual);

        $this->assertSame('success', $log->status->value);
        $this->assertSame(1, $log->records_imported);

        $employee = Employee::where('employee_id', 'EMP-900')->firstOrFail();
        $this->assertSame($company->id, $employee->company_id);
        $this->assertSame($department->id, $employee->department_id, 'department is now HR-synced, not Admin-assigned');
        $this->assertSame($designation->id, $employee->designation_id);
        $this->assertTrue($employee->is_active);
        $this->assertNotNull($employee->employee_status_id);
    }

    public function test_new_company_department_designation_names_are_auto_created_and_flagged_for_review(): void
    {
        $this->seed([CompanySeeder::class, DesignationSeeder::class, DepartmentSeeder::class]);

        Http::fake([
            'hr.example.test/api/employees' => Http::response([$this->record([
                'employee_id' => 'EMP-901',
                'company' => ['id' => null, 'name' => 'Cherry Ventures'],
                'department' => ['id' => null, 'name' => 'Growth'],
                'designation' => ['id' => null, 'name' => 'Growth Lead'],
            ])]),
        ]);

        $this->app->bind(HrSourceInterface::class, fn () => $this->source());

        app(HrSyncService::class)->sync(SyncType::Manual);

        $company = Company::where('name', 'Cherry Ventures')->firstOrFail();
        $this->assertTrue($company->needs_review);

        $department = Department::where('name', 'Growth')->where('company_id', $company->id)->firstOrFail();
        $this->assertTrue($department->needs_review);

        $designation = Designation::where('name', 'Growth Lead')->where('company_id', $company->id)->firstOrFail();
        $this->assertTrue($designation->needs_review);
    }

    public function test_matches_by_hr_ref_id_even_when_the_name_was_renamed_locally(): void
    {
        $company = Company::create(['hr_ref_id' => 501, 'name' => 'Cherry Digital Solutions', 'is_active' => true]);
        $department = Department::create(['hr_ref_id' => 601, 'company_id' => $company->id, 'name' => 'Engineering', 'is_active' => true]);
        $designation = Designation::create(['hr_ref_id' => 701, 'company_id' => $company->id, 'name' => 'Software Engineer', 'is_active' => true]);

        // An Admin has since renamed all three in OCED — HR still sends the old names.
        $company->update(['name' => 'Cherry Digital Solutions (Renamed)']);
        $department->update(['name' => 'Engineering (Renamed)']);
        $designation->update(['name' => 'Senior Software Engineer']);

        Http::fake([
            'hr.example.test/api/employees' => Http::response([$this->record([
                'employee_id' => 'EMP-902',
                'company' => ['id' => 501, 'name' => 'Cherry Digital Solutions'],
                'department' => ['id' => 601, 'name' => 'Engineering'],
                'designation' => ['id' => 701, 'name' => 'Software Engineer'],
            ])]),
        ]);

        $this->app->bind(HrSourceInterface::class, fn () => $this->source());

        $log = app(HrSyncService::class)->sync(SyncType::Manual);

        $this->assertSame('success', $log->status->value);
        $this->assertSame(1, Company::count());
        $this->assertSame(1, Department::count());
        $this->assertSame(1, Designation::count());

        $employee = Employee::where('employee_id', 'EMP-902')->firstOrFail();
        $this->assertSame($company->id, $employee->company_id);
        $this->assertSame($department->id, $employee->department_id);
        $this->assertSame($designation->id, $employee->designation_id);
    }

    public function test_backfills_hr_ref_id_onto_an_existing_name_matched_record(): void
    {
        $company = Company::create(['hr_ref_id' => null, 'name' => 'Cherry Digital Solutions', 'is_active' => true]);
        $designation = Designation::create(['hr_ref_id' => null, 'company_id' => $company->id, 'name' => 'Software Engineer', 'is_active' => true]);

        Http::fake([
            'hr.example.test/api/employees' => Http::response([$this->record([
                'employee_id' => 'EMP-903',
                'company' => ['id' => 502, 'name' => 'Cherry Digital Solutions'],
                'department' => ['id' => null, 'name' => null],
                'designation' => ['id' => 702, 'name' => 'Software Engineer'],
            ])]),
        ]);

        $this->app->bind(HrSourceInterface::class, fn () => $this->source());

        app(HrSyncService::class)->sync(SyncType::Manual);

        $this->assertSame(502, $company->fresh()->hr_ref_id);
        $this->assertSame(702, $designation->fresh()->hr_ref_id);
        $this->assertSame(1, Company::count());
    }

    public function test_employment_status_is_a_synced_lookup_not_a_hardcoded_bucket(): void
    {
        Http::fake([
            'hr.example.test/api/employees' => Http::response([$this->record([
                'employee_id' => 'EMP-904',
                'employment_status' => ['id' => 99, 'name' => 'Sabbatical'],
            ])]),
        ]);

        $this->app->bind(HrSourceInterface::class, fn () => $this->source());

        app(HrSyncService::class)->sync(SyncType::Manual);

        $status = EmployeeStatus::where('hr_ref_id', 99)->firstOrFail();
        $this->assertSame('Sabbatical', $status->name);

        $employee = Employee::where('employee_id', 'EMP-904')->firstOrFail();
        $this->assertSame($status->id, $employee->employee_status_id);
        $this->assertTrue($employee->is_active, 'is_active is presence-based, independent of the status label');
    }

    public function test_employee_absent_from_a_later_feed_is_marked_inactive(): void
    {
        // Http::fake() stubs accumulate rather than replace on repeated calls for
        // the same URL (the first-registered match always wins) — a response
        // sequence is the correct way to return different bodies across two
        // sync() calls to the same endpoint within one test.
        Http::fakeSequence('hr.example.test/api/employees')
            ->push([$this->record(['employee_id' => 'EMP-905'])])
            ->push([]);

        $this->app->bind(HrSourceInterface::class, fn () => $this->source());
        app(HrSyncService::class)->sync(SyncType::Manual);

        $employee = Employee::where('employee_id', 'EMP-905')->firstOrFail();
        $this->assertTrue($employee->is_active);

        app(HrSyncService::class)->sync(SyncType::Manual);

        $this->assertFalse($employee->fresh()->is_active);
    }
}
