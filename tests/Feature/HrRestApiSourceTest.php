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
        $this->assertSame('Software Engineer', $dto->designationName);
        $this->assertSame('active', $dto->employmentStatusCode);

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer 1|test-fake-token-not-a-real-credential'));
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
}
