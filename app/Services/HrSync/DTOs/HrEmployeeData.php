<?php

namespace App\Services\HrSync\DTOs;

/**
 * Mirrors exactly what the real HR API returns (GET /api/employees — see
 * HrRestApiSource and architecture-plan.md §2.4). This is deliberately a much
 * smaller contract than the underlying HR database table: department, dates,
 * job level, supervisor, and middle name are NOT exposed by the API, so they
 * are not part of this DTO — those fields are Admin-managed in OCED instead.
 */
final class HrEmployeeData
{
    public function __construct(
        public readonly string $employeeCode,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $email,
        public readonly string $companyName,
        public readonly ?string $designationName,
        public readonly string $employmentStatusCode,
    ) {}
}
