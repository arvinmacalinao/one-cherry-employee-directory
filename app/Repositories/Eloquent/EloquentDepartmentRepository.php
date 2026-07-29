<?php

namespace App\Repositories\Eloquent;

use App\Models\Department;
use App\Repositories\Contracts\DepartmentRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentDepartmentRepository implements DepartmentRepositoryInterface
{
    public function find(int $id): ?Department
    {
        return Department::with('company')->find($id);
    }

    public function findByHrRefId(int $hrRefId): ?Department
    {
        return Department::where('hr_ref_id', $hrRefId)->first();
    }

    public function allActiveWithCounts(): Collection
    {
        return Department::active()
            ->with('company')
            ->withCount(['employees' => fn ($q) => $q->visibleInDirectory()])
            ->orderBy('name')
            ->get();
    }

    public function forCompany(int $companyId): Collection
    {
        return Department::active()->where('company_id', $companyId)->orderBy('name')->get();
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
