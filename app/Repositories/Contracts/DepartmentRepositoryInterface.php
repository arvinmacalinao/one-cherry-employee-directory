<?php

namespace App\Repositories\Contracts;

use App\Models\Department;
use Illuminate\Support\Collection;

interface DepartmentRepositoryInterface
{
    public function find(int $id): ?Department;

    public function findByHrRefId(int $hrRefId): ?Department;

    public function allActiveWithCounts(): Collection;

    public function forCompany(int $companyId): Collection;

    public function count(): int;

    public function create(array $attributes): Department;

    public function update(Department $department, array $attributes): Department;

    public function delete(Department $department): bool;
}
