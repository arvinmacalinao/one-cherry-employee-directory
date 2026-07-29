<?php

namespace App\Repositories\Eloquent;

use App\Models\Department;
use App\Repositories\Contracts\DepartmentRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentDepartmentRepository implements DepartmentRepositoryInterface
{
    public function find(int $id): ?Department
    {
        return Department::find($id);
    }

    public function findByHrRefId(int $hrRefId): ?Department
    {
        return Department::where('hr_ref_id', $hrRefId)->first();
    }

    public function allActiveWithCounts(): Collection
    {
        return Department::active()
            ->withCount(['employees' => fn ($q) => $q->visibleInDirectory()])
            ->orderBy('name')
            ->get();
    }

    /**
     * Department is org-wide, not company-owned — "for a company" means
     * "has at least one visible employee at that company", and the count is
     * scoped to that company's headcount specifically (not the department's
     * org-wide total), for use on a Company's detail page.
     */
    public function forCompany(int $companyId): Collection
    {
        return Department::active()
            ->whereHas('employees', fn ($q) => $q->where('company_id', $companyId)->visibleInDirectory())
            ->withCount(['employees' => fn ($q) => $q->where('company_id', $companyId)->visibleInDirectory()])
            ->orderBy('name')
            ->get();
    }

    public function count(): int
    {
        return Department::active()->count();
    }

    public function create(array $attributes): Department
    {
        return Department::create($attributes);
    }

    public function update(Department $department, array $attributes): Department
    {
        $department->update($attributes);

        return $department->refresh();
    }

    public function delete(Department $department): bool
    {
        return (bool) $department->delete();
    }
}
