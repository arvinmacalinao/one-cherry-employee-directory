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
 *   already filters server-side to `where('status', 'active')`, so:
 *     - Every record this returns has status = "active". Resigned/inactive
 *       employees simply disappear from the feed; HrSyncService's existing
 *       "missing from feed -> mark inactive" pass is what actually detects
 *       departures — there is no explicit resignation signal to read here.
 *     - Employees on leave, if HR tracks that as a *different* status value,
 *       would also disappear from this feed and get marked inactive by the
 *       same pass. This is a known gap — flag if HR's `status` column has a
 *       distinct "on_leave" value that should stay visible in the directory.
 *   Record shape: { employee_id, name, email, company, designation, status }
 *     - `employee_id` here is the human-readable code (what the rest of this
 *       app calls employee_code / employees.employee_id).
 *     - `name` is a single string, naively split on the first space into
 *       first/last (see splitName()) — multi-word first names, suffixes, or
 *       "Last, First" formats will not split correctly. Revisit if HR's
 *       naming convention makes this a real problem in practice.
 *     - `company` / `designation` are resolved display names, not IDs —
 *       HrSyncService now matches Company/Designation by name rather than
 *       by a numeric hr_ref_id for records coming through this source.
 *     - No department, dates, job level, or supervisor are exposed by this
 *       endpoint at all — those fields are Admin-managed in OCED instead of
 *       HR-synced. See architecture-plan.md §2.4 for the full rationale.
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
        $company = $record['company'] ?? null;

        if (! $employeeCode || ! $email || ! $company) {
            Log::warning('HR API: skipping unusable employee record (missing employee_id, email, or company)', ['record' => $record]);

            return null;
        }

        [$firstName, $lastName] = $this->splitName((string) ($record['name'] ?? ''));

        return new HrEmployeeData(
            employeeCode: (string) $employeeCode,
            firstName: $firstName,
            lastName: $lastName,
            email: (string) $email,
            companyName: (string) $company,
            designationName: $record['designation'] ?? null,
            employmentStatusCode: (string) ($record['status'] ?? 'active'),
        );
    }

    /**
     * @return array{0: string, 1: string} [firstName, lastName]
     */
    protected function splitName(string $name): array
    {
        $name = trim($name);
        $parts = explode(' ', $name, 2);

        return [$parts[0] ?: $name, $parts[1] ?? ''];
    }
}
