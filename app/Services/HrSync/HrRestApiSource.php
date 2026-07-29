<?php

namespace App\Services\HrSync;

use App\Services\HrSync\Contracts\HrSourceInterface;
use App\Services\HrSync\DTOs\HrEmployeeData;
use App\Services\HrSync\Exceptions\HrApiException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Pulls the employee roster from the real HR REST API — confirmed contract:
 *
 *   GET {HR_SYNC_API_URL}{endpoint}   (default endpoint: /api/employees)
 *   Auth: Sanctum bearer token (HR_SYNC_API_KEY) via the Authorization header.
 *   Response: a plain JSON array (NOT a paginated resource) — the endpoint
 *   already filters server-side to `where('u_active', 1)` (HR's account-active
 *   flag, not the same thing as employment_status — see architecture-plan.md §2.5).
 *
 *   Record shape:
 *     employee_id, first_name, middle_name, last_name, name, username, email,
 *     company: {id, name}, department: {id, name}, designation: {id, name},
 *     supervisor: {id, employee_id, name},
 *     employment_status: {id, name}, job_level: {id, name} (received, unused),
 *     date_hired, date_regularized, date_separated, created_at, updated_at.
 *
 *   Department, Supervisor, and the three employment dates are now genuinely
 *   HR-owned and sync-controlled — this endpoint replaced an earlier, much
 *   smaller contract that didn't expose any of them. See architecture-plan.md §2.5.
 *
 *   `email` is frequently null in practice — a real data-quality gap on HR's
 *   side, not something this class papers over. Only employee_id/first_name/
 *   last_name are hard-required to map a record; everything else, including
 *   email, is nullable straight through to the employees table.
 *
 * Bound in place of FakeHrSource when HR_SYNC_SOURCE=rest_api (see AppServiceProvider).
 */
class HrRestApiSource implements HrSourceInterface
{
    public function __construct(
        protected string $baseUrl,
        protected ?string $apiKey,
        protected string $endpoint = '/api/employees',
        protected int $timeout = 30,
    ) {}

    public function fetchEmployees(): Collection
    {
        $url = rtrim($this->baseUrl, '/').$this->endpoint;

        try {
            $response = Http::withToken($this->apiKey)
                ->acceptJson()
                ->timeout($this->timeout)
                ->retry(2, 500)
                ->get($url)
                ->throw();
        } catch (Throwable $e) {
            throw new HrApiException("HR API request failed: {$e->getMessage()}", previous: $e);
        }

        $records = $response->json();

        // Tolerate a future switch to a paginated {"data": [...]} shape without a code change.
        if (isset($records['data']) && is_array($records['data'])) {
            $records = $records['data'];
        }

        return collect($records)
            ->map(fn (array $record) => $this->mapRecord($record))
            ->filter()
            ->values();
    }

    protected function mapRecord(array $record): ?HrEmployeeData
    {
        $employeeCode = $record['employee_id'] ?? null;
        $email = $record['email'] ?? null;
        $firstName = $record['first_name'] ?? null;
        $lastName = $record['last_name'] ?? null;

        // Email is HR-owned but not guaranteed present — plenty of real employees
        // come through with a null email (a data-quality gap on HR's side, not
        // something OCED papers over). They still import; they just don't get a
        // mailto link on their profile until HR fills it in.
        if (! $employeeCode || ! $firstName || ! $lastName) {
            // A compact summary, not the full record — dumping the whole payload here
            // ballooned laravel.log into tens of megabytes within days against a real
            // HR feed with many unusable rows, and eventually caused the log writer
            // itself to exhaust memory. Enough context to find the row in HR's system
            // (employee_id, or the raw array if even that's missing) without repeating
            // this at scale.
            Log::warning('HR API: skipping unusable employee record (missing employee_id, first_name, or last_name)', [
                'employee_id' => $employeeCode ?? $record,
            ]);

            return null;
        }

        $company = $record['company'] ?? [];
        $department = $record['department'] ?? [];
        $designation = $record['designation'] ?? [];
        $supervisor = $record['supervisor'] ?? [];
        $status = $record['employment_status'] ?? [];

        return new HrEmployeeData(
            employeeCode: (string) $employeeCode,
            firstName: (string) $firstName,
            middleName: $record['middle_name'] ?? null,
            lastName: (string) $lastName,
            username: $record['username'] ?? null,
            email: $email !== null ? (string) $email : null,
            companyId: isset($company['id']) ? (int) $company['id'] : null,
            companyName: $company['name'] ?? null,
            departmentId: isset($department['id']) ? (int) $department['id'] : null,
            departmentName: $department['name'] ?? null,
            designationId: isset($designation['id']) ? (int) $designation['id'] : null,
            designationName: $designation['name'] ?? null,
            supervisorEmployeeCode: $supervisor['employee_id'] ?? null,
            employmentStatusId: isset($status['id']) ? (int) $status['id'] : null,
            employmentStatusName: $status['name'] ?? null,
            dateHired: $record['date_hired'] ?? null,
            dateRegularized: $record['date_regularized'] ?? null,
            dateSeparated: $record['date_separated'] ?? null,
        );
    }
}
