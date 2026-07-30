<?php

namespace App\Services\HrSync\DTOs;

/**
 * Mirrors the real HR API response — GET /api/employees, see HrRestApiSource and
 * architecture-plan.md §2.5. Every field here is HR-owned; nothing in this DTO
 * is ever written into employee_profiles.
 */
final class HrEmployeeData
{
    public function __construct(
        public readonly string $employeeCode,
        public readonly bool $isActiveInHr,
        public readonly string $firstName,
        public readonly ?string $middleName,
        public readonly string $lastName,
        public readonly ?string $username,
        public readonly ?string $email,
        public readonly ?int $companyId,
        public readonly ?string $companyName,
        public readonly ?int $departmentId,
        public readonly ?string $departmentName,
        public readonly ?int $designationId,
        public readonly ?string $designationName,
        public readonly ?string $supervisorEmployeeCode,
        public readonly ?int $employmentStatusId,
        public readonly ?string $employmentStatusName,
        public readonly ?string $dateHired,
        public readonly ?string $dateRegularized,
        public readonly ?string $dateSeparated,
    ) {}
}
