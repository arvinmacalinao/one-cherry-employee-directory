<?php

namespace App\Repositories\Contracts;

use App\Models\OfficeLocation;
use Illuminate\Support\Collection;

interface OfficeLocationRepositoryInterface
{
    public function find(int $id): ?OfficeLocation;

    public function allActive(): Collection;

    public function count(): int;

    public function create(array $attributes): OfficeLocation;

    public function update(OfficeLocation $officeLocation, array $attributes): OfficeLocation;

    public function delete(OfficeLocation $officeLocation): bool;
}
