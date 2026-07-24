<?php

namespace App\Repositories\Eloquent;

use App\Models\OfficeLocation;
use App\Repositories\Contracts\OfficeLocationRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentOfficeLocationRepository implements OfficeLocationRepositoryInterface
{
    public function find(int $id): ?OfficeLocation
    {
        return OfficeLocation::with('company')->find($id);
    }

    public function allActive(): Collection
    {
        return OfficeLocation::active()->with('company')->orderBy('name')->get();
    }

    public function count(): int
    {
        return OfficeLocation::active()->count();
    }

    public function create(array $attributes): OfficeLocation
    {
        return OfficeLocation::create($attributes);
    }

    public function update(OfficeLocation $officeLocation, array $attributes): OfficeLocation
    {
        $officeLocation->update($attributes);

        return $officeLocation->refresh();
    }

    public function delete(OfficeLocation $officeLocation): bool
    {
        return (bool) $officeLocation->delete();
    }
}
