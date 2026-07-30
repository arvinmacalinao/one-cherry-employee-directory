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
            'u_active' => true,
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

    public function test_a_null_email_does_not_skip_the_record(): void
    {
        // Real HR data frequently has no email on file for an employee — that's
        // a data-quality gap on HR's side, not a reason to drop the whole record.
        // Only employee_id/first_name/last_name are hard-required. See §2.5.
        Http::fake([
            'hr.example.test/api/employees' => Http::response([
                $this->record(['employee_id' => 'EMP-200', 'email' => null]),
            ]),
        ]);

        $results = $this->source()->fetchEmployees();

        $this->assertCount(1, $results);
        $this->assertNull($results->first()->email);
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
        $department = Department::where('name', 'Engineering')->firstOrFail();
        $designation = Designation::where('name', 'Software Engineer')->firstOrFail();

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

    public function test_sync_imports_an_employee_with_no_email_on_file(): void
    {
        $this->seed([CompanySeeder::class, DesignationSeeder::class, DepartmentSeeder::class]);

        Http::fake([
            'hr.example.test/api/employees' => Http::response([$this->record(['employee_id' => 'EMP-950', 'email' => null])]),
        ]);
        $this->app->bind(HrSourceInterface::class, fn () => $this->source());

        $log = app(HrSyncService::class)->sync(SyncType::Manual);

        $this->assertSame('success', $log->status->value);
        $this->assertSame(1, $log->records_imported);

        $employee = Employee::where('employee_id', 'EMP-950')->firstOrFail();
        $this->assertNull($employee->email);
        $this->assertTrue($employee->is_active);
    }

    public function test_a_later_null_email_does_not_clobber_an_admin_entered_email(): void
    {
        // Email is HR-owned only when HR actually sends one — an Admin filling
        // the gap in via /admin/employees must survive subsequent syncs that
        // keep sending null for this employee. See HrSyncService::upsertEmployee().
        $this->seed([CompanySeeder::class, DesignationSeeder::class, DepartmentSeeder::class]);

        Http::fake([
            'hr.example.test/api/employees' => Http::response([$this->record(['employee_id' => 'EMP-970', 'email' => null])]),
        ]);
        $this->app->bind(HrSourceInterface::class, fn () => $this->source());
        app(HrSyncService::class)->sync(SyncType::Manual);

        $employee = Employee::where('employee_id', 'EMP-970')->firstOrFail();
        $employee->update(['email' => 'admin.entered@onecherry.group']);

        app(HrSyncService::class)->sync(SyncType::Manual);

        $this->assertSame('admin.entered@onecherry.group', $employee->fresh()->email);
    }

    public function test_hr_providing_a_real_email_later_overrides_the_admin_entered_one(): void
    {
        // HR's data still wins the moment it actually has a value — the Admin
        // entry was only ever a fallback for the gap, not a permanent override.
        $this->seed([CompanySeeder::class, DesignationSeeder::class, DepartmentSeeder::class]);

        Http::fakeSequence('hr.example.test/api/employees')
            ->push([$this->record(['employee_id' => 'EMP-971', 'email' => null])])
            ->push([$this->record(['employee_id' => 'EMP-971', 'email' => 'real.hr.email@onecherry.group'])]);

        $this->app->bind(HrSourceInterface::class, fn () => $this->source());
        app(HrSyncService::class)->sync(SyncType::Manual);

        $employee = Employee::where('employee_id', 'EMP-971')->firstOrFail();
        $employee->update(['email' => 'admin.entered@onecherry.group']);

        app(HrSyncService::class)->sync(SyncType::Manual);

        $this->assertSame('real.hr.email@onecherry.group', $employee->fresh()->email);
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

        $department = Department::where('name', 'Growth')->firstOrFail();
        $this->assertTrue($department->needs_review);

        $designation = Designation::where('name', 'Growth Lead')->firstOrFail();
        $this->assertTrue($designation->needs_review);
    }

    public function test_hr_reusing_a_department_id_across_companies_resolves_to_one_shared_department(): void
    {
        // Department/Designation are org-wide master data, not per-company —
        // confirmed by the client: "there should only be one Sales, one IT,
        // one HR, regardless of company." HR reuses ug_id=39 across many
        // companies precisely because it's the same department, not a
        // per-company duplicate. Two employees at two different companies
        // both in ug_id=39 must resolve to the *same* Department row.
        $this->seed([CompanySeeder::class, DesignationSeeder::class, DepartmentSeeder::class]);

        Http::fake([
            'hr.example.test/api/employees' => Http::response([
                $this->record([
                    'employee_id' => 'EMP-960',
                    'email' => 'alpha.rep@onecherry.group',
                    'company' => ['id' => null, 'name' => 'Company Alpha'],
                    'department' => ['id' => 39, 'name' => 'Sales'],
                    'designation' => ['id' => null, 'name' => 'Sales Rep'],
                ]),
                $this->record([
                    'employee_id' => 'EMP-961',
                    'email' => 'beta.rep@onecherry.group',
                    'company' => ['id' => null, 'name' => 'Company Beta'],
                    'department' => ['id' => 39, 'name' => 'Sales'],
                    'designation' => ['id' => null, 'name' => 'Sales Rep'],
                ]),
            ]),
        ]);

        $this->app->bind(HrSourceInterface::class, fn () => $this->source());

        $log = app(HrSyncService::class)->sync(SyncType::Manual);

        $this->assertSame('success', $log->status->value);
        $this->assertSame(2, $log->records_imported);
        $this->assertEmpty($log->errors);

        $this->assertSame(1, Department::where('hr_ref_id', 39)->count(), 'ug_id=39 must resolve to a single shared department row, not one per company');
        $this->assertSame(1, Designation::where('name', 'Sales Rep')->count());

        $alphaEmployee = Employee::where('employee_id', 'EMP-960')->firstOrFail();
        $betaEmployee = Employee::where('employee_id', 'EMP-961')->firstOrFail();

        $this->assertSame($alphaEmployee->department_id, $betaEmployee->department_id, 'both employees share the same Sales department despite being at different companies');
        $this->assertNotSame($alphaEmployee->company_id, $betaEmployee->company_id, 'their companies must still differ — company comes from the employee, not the department');
    }

    public function test_matches_by_hr_ref_id_even_when_the_name_was_renamed_locally(): void
    {
        $company = Company::create(['hr_ref_id' => 501, 'name' => 'Cherry Digital Solutions', 'is_active' => true]);
        $department = Department::create(['hr_ref_id' => 601, 'name' => 'Engineering', 'is_active' => true]);
        $designation = Designation::create(['hr_ref_id' => 701, 'name' => 'Software Engineer', 'is_active' => true]);

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
        $designation = Designation::create(['hr_ref_id' => null, 'name' => 'Software Engineer', 'is_active' => true]);

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

    public function test_a_renamed_hr_status_updates_the_stored_name_on_the_next_sync(): void
    {
        // Regression: an ID match used to just return the name already stored,
        // never checking whether HR's current name for that es_id had changed.
        // In production this meant an initial guessed/seeded name for an es_id
        // stuck forever — 573 real "Regular" employees were mislabeled "On Leave"
        // because a placeholder seed happened to claim es_id=3 first. There's no
        // Admin-editable branding surface for a status the way there is for a
        // company, so an ID match must always keep the name in sync with HR.
        $stale = EmployeeStatus::create(['hr_ref_id' => 3, 'name' => 'On Leave']);

        Http::fake([
            'hr.example.test/api/employees' => Http::response([$this->record([
                'employee_id' => 'EMP-980',
                'employment_status' => ['id' => 3, 'name' => 'Regular'],
            ])]),
        ]);

        $this->app->bind(HrSourceInterface::class, fn () => $this->source());

        app(HrSyncService::class)->sync(SyncType::Manual);

        $this->assertSame('Regular', $stale->fresh()->name);

        $employee = Employee::where('employee_id', 'EMP-980')->firstOrFail();
        $this->assertSame($stale->id, $employee->employee_status_id, 'must still resolve to the same row by hr_ref_id, not create a duplicate');
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

    public function test_hr_rest_api_source_maps_the_u_active_flag(): void
    {
        Http::fake([
            'hr.example.test/api/employees' => Http::response([
                $this->record(['employee_id' => 'EMP-990', 'u_active' => false]),
            ]),
        ]);

        $dto = $this->source()->fetchEmployees()->first();

        $this->assertFalse($dto->isActiveInHr);
    }

    public function test_missing_u_active_field_defaults_to_active(): void
    {
        // Backward-compat for an HR response from before this flag existed.
        $record = $this->record(['employee_id' => 'EMP-991']);
        unset($record['u_active']);

        Http::fake([
            'hr.example.test/api/employees' => Http::response([$record]),
        ]);

        $dto = $this->source()->fetchEmployees()->first();

        $this->assertTrue($dto->isActiveInHr);
    }

    public function test_an_inactive_employee_is_synced_but_hidden_not_omitted(): void
    {
        Http::fake([
            'hr.example.test/api/employees' => Http::response([
                $this->record(['employee_id' => 'EMP-992', 'u_active' => false]),
            ]),
        ]);
        $this->app->bind(HrSourceInterface::class, fn () => $this->source());

        $log = app(HrSyncService::class)->sync(SyncType::Manual);

        $this->assertSame('success', $log->status->value);
        $this->assertSame(1, $log->records_imported, 'HR sending an inactive employee is still a real import — it just starts hidden');

        $employee = Employee::where('employee_id', 'EMP-992')->firstOrFail();
        $this->assertFalse($employee->is_active);
    }

    public function test_a_flip_to_inactive_is_counted_as_deactivated_not_updated(): void
    {
        Http::fakeSequence('hr.example.test/api/employees')
            ->push([$this->record(['employee_id' => 'EMP-993', 'u_active' => true])])
            ->push([$this->record(['employee_id' => 'EMP-993', 'u_active' => false])]);

        $this->app->bind(HrSourceInterface::class, fn () => $this->source());
        app(HrSyncService::class)->sync(SyncType::Manual);

        $log = app(HrSyncService::class)->sync(SyncType::Manual);

        $this->assertSame(1, $log->records_deactivated);
        $this->assertSame(0, $log->records_updated);
        $this->assertFalse(Employee::where('employee_id', 'EMP-993')->firstOrFail()->is_active);
    }

    public function test_a_flip_back_to_active_reactivates_the_employee(): void
    {
        Http::fakeSequence('hr.example.test/api/employees')
            ->push([$this->record(['employee_id' => 'EMP-994', 'u_active' => false])])
            ->push([$this->record(['employee_id' => 'EMP-994', 'u_active' => true])]);

        $this->app->bind(HrSourceInterface::class, fn () => $this->source());
        app(HrSyncService::class)->sync(SyncType::Manual);
        $this->assertFalse(Employee::where('employee_id', 'EMP-994')->firstOrFail()->is_active);

        app(HrSyncService::class)->sync(SyncType::Manual);

        $this->assertTrue(Employee::where('employee_id', 'EMP-994')->firstOrFail()->fresh()->is_active);
    }

    public function test_supervisor_resolves_even_when_the_supervisors_own_account_is_inactive(): void
    {
        // This is the exact real-world scenario that motivated sending inactive
        // employees at all: a deactivated manager's own account used to be
        // omitted from the feed entirely, so every one of their reports
        // permanently logged an unresolved-supervisor warning. Now that HR
        // sends inactive accounts too (just flagged, not omitted), the
        // supervisor link resolves normally — it's just hidden from the
        // public directory, exactly like any other inactive employee.
        Http::fake([
            'hr.example.test/api/employees' => Http::response([
                $this->record([
                    'employee_id' => 'BPJ0015',
                    'email' => 'deactivated.manager@onecherry.group',
                    'first_name' => 'Deactivated',
                    'last_name' => 'Manager',
                    'u_active' => false,
                ]),
                $this->record([
                    'employee_id' => 'BPJ0002',
                    'email' => 'active.report@onecherry.group',
                    'first_name' => 'Active',
                    'last_name' => 'Report',
                    'u_active' => true,
                    'supervisor' => ['id' => null, 'employee_id' => 'BPJ0015', 'name' => 'Deactivated Manager'],
                ]),
            ]),
        ]);

        $this->app->bind(HrSourceInterface::class, fn () => $this->source());

        $log = app(HrSyncService::class)->sync(SyncType::Manual);

        $supervisorWarnings = array_filter($log->warnings, fn ($w) => str_contains($w, 'Could not resolve supervisor'));
        $this->assertEmpty($supervisorWarnings, 'the supervisor is present in the feed now, just inactive — no unresolved-supervisor warning should fire');

        $supervisor = Employee::where('employee_id', 'BPJ0015')->firstOrFail();
        $report = Employee::where('employee_id', 'BPJ0002')->firstOrFail();

        $this->assertFalse($supervisor->is_active, 'the supervisor is hidden from the public directory...');
        $this->assertSame($supervisor->id, $report->immediate_supervisor_id, '...but the reporting link still resolves correctly');
    }

    public function test_preview_flags_a_would_be_deactivation_from_the_u_active_flag(): void
    {
        // Http::fake() stubs accumulate rather than replace for the same URL —
        // a response sequence is required to vary the payload across two calls.
        Http::fakeSequence('hr.example.test/api/employees')
            ->push([$this->record(['employee_id' => 'EMP-995'])])
            ->push([$this->record(['employee_id' => 'EMP-995', 'u_active' => false])]);

        $this->app->bind(HrSourceInterface::class, fn () => $this->source());
        app(HrSyncService::class)->sync(SyncType::Manual);

        $preview = app(HrSyncService::class)->preview();

        $this->assertCount(1, $preview->becomingInactive);
        $this->assertSame('EMP-995', $preview->becomingInactive[0]['employee_id']);

        // Still hasn't written anything.
        $this->assertTrue(Employee::where('employee_id', 'EMP-995')->firstOrFail()->is_active);
    }
}
