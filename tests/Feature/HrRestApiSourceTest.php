<?php

namespace Tests\Feature;

use App\Enums\SyncType;
use App\Models\Company;
use App\Models\Designation;
use App\Models\Employee;
use App\Services\HrSync\Contracts\HrSourceInterface;
use App\Services\HrSync\Exceptions\HrApiException;
use App\Services\HrSync\HrRestApiSource;
use App\Services\HrSync\HrSyncService;
use Database\Seeders\CompanySeeder;
use Database\Seeders\DesignationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Verifies HrRestApiSource against the real, confirmed contract:
 *   GET {base}/api/employees -> plain JSON array (no pagination wrapper),
 *   filtered server-side to status="active", records shaped as
 *   { employee_id, name, email, company, designation, status }.
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
            'name' => 'Ramon Sy',
            'email' => 'ramon.sy@onecherry.group',
            'company' => 'Cherry Digital Solutions',
            'designation' => 'Software Engineer',
            'status' => 'active',
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
        $this->assertSame('ramon.sy@onecherry.group', $dto->email);
        $this->assertSame('Cherry Digital Solutions', $dto->companyName);
        $this->assertNull($dto->companyId, 'HR responses that predate company_id/designation_id must still map cleanly');
        $this->assertSame('Software Engineer', $dto->designationName);
        $this->assertNull($dto->designationId);
        $this->assertSame('active', $dto->employmentStatusCode);

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer 1|test-fake-token-not-a-real-credential'));
    }

    public function test_maps_company_id_and_designation_id_when_hr_sends_them(): void
    {
        Http::fake([
            'hr.example.test/api/employees' => Http::response([
                $this->record(['company_id' => 12, 'designation_id' => 34]),
            ]),
        ]);

        $dto = $this->source()->fetchEmployees()->first();

        $this->assertSame(12, $dto->companyId);
        $this->assertSame(34, $dto->designationId);
    }

    public function test_splits_multi_word_names_naively_on_first_space(): void
    {
        Http::fake([
            'hr.example.test/api/employees' => Http::response([
                $this->record(['name' => 'Maria Clara Dela Cruz']),
            ]),
        ]);

        $dto = $this->source()->fetchEmployees()->first();

        $this->assertSame('Maria', $dto->firstName);
        $this->assertSame('Clara Dela Cruz', $dto->lastName);
    }

    public function test_skips_records_missing_required_fields_without_failing_the_batch(): void
    {
        Http::fake([
            'hr.example.test/api/employees' => Http::response([
                $this->record(['employee_id' => 'EMP-100']),
                $this->record(['employee_id' => 'EMP-101', 'company' => null]),
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

    public function test_end_to_end_sync_matches_company_and_designation_by_name(): void
    {
        $this->seed([CompanySeeder::class, DesignationSeeder::class]);

        $company = Company::where('name', 'Cherry Digital Solutions')->firstOrFail();
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
        $this->assertSame($designation->id, $employee->designation_id);
        $this->assertNull($employee->department_id, 'department is not HR-synced — must stay unset until an Admin assigns it');
    }

    public function test_new_company_and_designation_names_are_auto_created_and_flagged_for_review(): void
    {
        $this->seed([CompanySeeder::class, DesignationSeeder::class]);

        Http::fake([
            'hr.example.test/api/employees' => Http::response([$this->record([
                'employee_id' => 'EMP-901',
                'company' => 'Cherry Ventures',
                'designation' => 'Growth Lead',
            ])]),
        ]);

        $this->app->bind(HrSourceInterface::class, fn () => $this->source());

        $log = app(HrSyncService::class)->sync(SyncType::Manual);

        $this->assertSame('partial', $log->status->value, 'auto-creating a new company/designation should be flagged, not silently clean');
        $this->assertNotEmpty($log->errors);

        $company = Company::where('name', 'Cherry Ventures')->firstOrFail();
        $this->assertTrue($company->needs_review);

        $designation = Designation::where('name', 'Growth Lead')->where('company_id', $company->id)->firstOrFail();
        $this->assertTrue($designation->needs_review);
    }

    public function test_matches_company_and_designation_by_hr_ref_id_even_when_the_name_was_renamed_locally(): void
    {
        $company = Company::create(['hr_ref_id' => 501, 'name' => 'Cherry Digital Solutions', 'is_active' => true]);
        $designation = Designation::create(['hr_ref_id' => 701, 'company_id' => $company->id, 'name' => 'Software Engineer', 'hierarchy_level' => 1, 'is_active' => true]);

        // An Admin has since renamed both records in OCED — HR still sends the old names.
        $company->update(['name' => 'Cherry Digital Solutions (Renamed)']);
        $designation->update(['name' => 'Senior Software Engineer']);

        Http::fake([
            'hr.example.test/api/employees' => Http::response([$this->record([
                'employee_id' => 'EMP-902',
                'company_id' => 501,
                'designation_id' => 701,
            ])]),
        ]);

        $this->app->bind(HrSourceInterface::class, fn () => $this->source());

        $log = app(HrSyncService::class)->sync(SyncType::Manual);

        $this->assertSame('success', $log->status->value, 'ID match should succeed without falling back to name and creating a duplicate');
        $this->assertSame(1, Company::count(), 'must not create a duplicate company for the stale name HR still sends');
        $this->assertSame(1, Designation::count());

        $employee = Employee::where('employee_id', 'EMP-902')->firstOrFail();
        $this->assertSame($company->id, $employee->company_id);
        $this->assertSame($designation->id, $employee->designation_id);
    }

    public function test_backfills_hr_ref_id_onto_an_existing_name_matched_company_and_designation(): void
    {
        $company = Company::create(['hr_ref_id' => null, 'name' => 'Cherry Digital Solutions', 'is_active' => true]);
        $designation = Designation::create(['hr_ref_id' => null, 'company_id' => $company->id, 'name' => 'Software Engineer', 'hierarchy_level' => 1, 'is_active' => true]);

        Http::fake([
            'hr.example.test/api/employees' => Http::response([$this->record([
                'employee_id' => 'EMP-903',
                'company_id' => 502,
                'designation_id' => 702,
            ])]),
        ]);

        $this->app->bind(HrSourceInterface::class, fn () => $this->source());

        app(HrSyncService::class)->sync(SyncType::Manual);

        $this->assertSame(502, $company->fresh()->hr_ref_id);
        $this->assertSame(702, $designation->fresh()->hr_ref_id);
        $this->assertSame(1, Company::count(), 'name match should reuse the existing row, not create a second one');
    }
}
