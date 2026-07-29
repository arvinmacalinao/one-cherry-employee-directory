<?php

namespace App\Services\HrSync\DTOs;

use Livewire\Wireable;

/**
 * A pure read/diff — never persists anything. See HrSyncService::preview() and
 * architecture-plan.md §2.5. Every array here is a list of associative arrays
 * shaped for direct display, not model instances (nothing was created to bind them to).
 *
 * Implements Wireable so it can be held directly as a public property on the
 * Admin\Sync Livewire component across requests.
 */
final class SyncPreviewResult implements Wireable
{
    public function __construct(
        public readonly array $newEmployees = [],
        public readonly array $updatedEmployees = [],
        public readonly array $departmentChanges = [],
        public readonly array $designationChanges = [],
        public readonly array $supervisorChanges = [],
        public readonly array $statusChanges = [],
        public readonly array $becomingInactive = [],
        public readonly array $warnings = [],
    ) {}

    public function isEmpty(): bool
    {
        return empty($this->newEmployees)
            && empty($this->updatedEmployees)
            && empty($this->becomingInactive);
    }

    public function toLivewire(): array
    {
        return [
            'newEmployees' => $this->newEmployees,
            'updatedEmployees' => $this->updatedEmployees,
            'departmentChanges' => $this->departmentChanges,
            'designationChanges' => $this->designationChanges,
            'supervisorChanges' => $this->supervisorChanges,
            'statusChanges' => $this->statusChanges,
            'becomingInactive' => $this->becomingInactive,
            'warnings' => $this->warnings,
        ];
    }

    public static function fromLivewire($value): static
    {
        return new static(...$value);
    }
}
