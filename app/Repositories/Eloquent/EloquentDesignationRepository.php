<?php

namespace App\Repositories\Eloquent;

use App\Models\Designation;
use App\Repositories\Contracts\DesignationRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentDesignationRepository implements DesignationRepositoryInterface
{
    public function find(int $id): ?Designation
    {
        return Designation::with('company')->find($id);
    }

    public function findByHrRefId(int $hrRefId): ?Designation
    {
        return Designation::where('hr_ref_id', $hrRefId)->first();
    }

    public function forCompany(int $companyId): Collection
    {
        return Designation::active()->where('company_id', $companyId)->orderBy('name')->get();
    }

    public function create(array $attributes): Designation
    {
        return Designation::create($attributes);
    }

    public function update(Designation $designation, array $attributes): Designation
    {
        $designation->update($attributes);

        return $designation->refresh();
    }

    public function delete(Designation $designation): bool
    {
        return (bool) $designation->delete();
    }
}
