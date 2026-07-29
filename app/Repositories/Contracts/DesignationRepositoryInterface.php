<?php

namespace App\Repositories\Contracts;

use App\Models\Designation;

interface DesignationRepositoryInterface
{
    public function find(int $id): ?Designation;

    public function findByHrRefId(int $hrRefId): ?Designation;

    public function create(array $attributes): Designation;

    public function update(Designation $designation, array $attributes): Designation;

    public function delete(Designation $designation): bool;
}
