<?php

namespace App\Services\HrSync\Contracts;

use App\Services\HrSync\DTOs\HrEmployeeData;
use Illuminate\Support\Collection;

/**
 * The seam between OCED and whatever the HR system actually is. Today's binding is
 * FakeHrSource (local dev). A real HrRestApiSource — or, later, a DirectorySourceInterface
 * for Active Directory / Google Workspace — plugs in beside it without HrSyncService
 * or anything upstream of it changing. See architecture-plan.md §2.4.
 */
interface HrSourceInterface
{
    /**
     * @return Collection<int, HrEmployeeData>
     */
    public function fetchEmployees(): Collection;
}
