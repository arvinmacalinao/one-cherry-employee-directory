<?php

namespace App\Services\HrSync\DTOs;

/**
 * Mirrors exactly what the real HR API returns (GET /api/employees — see
 * HrRestApiSource and architecture-plan.md §2.4). This is deliberately a much
 * smaller contract than the underlying HR database table: department, dates,
 * job level, supervisor, and middle name are NOT exposed by the API, so they
 * are not part of this DTO — those fields are Admin-managed in OCED instead.
 *
 * companyId/designationId are HR's own numeric FK values (their users.company_id
 * / users.designation_id), added alongside the existing name strings so
 * HrSyncService can match by stable ID instead of by name — see the
 * "Proposed fix for the name-based re-matching risk" note in architecture-plan.md
 * §2.4. They're nullable because older/unpatched HR responses may not send them
 * yet; HrSyncService falls back to name-matching when null.
 */
final class HrEmployeeData
{
    public function __construct(
        public readonly string $employeeCode,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $email,
        public readonly string $companyName,
        public readonly ?int $companyId,
        public readonly ?string $designationName,
        public readonly ?int $designationId,
        public readonly string $employmentStatusCode,
    ) {}
}
