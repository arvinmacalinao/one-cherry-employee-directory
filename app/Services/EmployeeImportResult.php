<?php

namespace App\Services;

/**
 * Result of EmployeeCsvService::import() — a pure summary, nothing more to hold
 * onto after the request. See EmployeeCsvService for the actual import rules.
 */
final class EmployeeImportResult
{
    public function __construct(
        public readonly int $rowsProcessed = 0,
        public readonly int $employeesUpdated = 0,
        public readonly array $warnings = [],
    ) {}
}
