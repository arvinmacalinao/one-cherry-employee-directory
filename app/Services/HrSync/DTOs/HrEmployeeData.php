<?php

namespace App\Services\HrSync\DTOs;

/**
 * Mirrors the HR system's employee/user table (see architecture-plan.md §2.4).
 * Only the fields HR actually owns — this is the entire contract between
 * HrSyncService and whatever HrSourceInterface implementation fetches it.
 */
final class HrEmployeeData
{
    public function __construct(
        public readonly string $employeeCode,
        public readonly string $firstName,
        public readonly ?string $middleName,
        public readonly string $lastName,
        public readonly string $email,
        public readonly int $companyHrRefId,
        public readonly int $departmentHrRefId,
        public readonly int $designationHrRefId,
        public readonly ?string $supervisorEmployeeCode,
        public readonly int|string $employmentStatusCode,
        public readonly ?string $dateHired,
        public readonly ?string $dateRegularized,
        public readonly ?string $dateSeparated,
        public readonly ?int $jobLevel,
    ) {}
}
