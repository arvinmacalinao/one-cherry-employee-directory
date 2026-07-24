<?php

namespace App\Repositories\Contracts;

use App\Models\Company;
use Illuminate\Support\Collection;

interface CompanyRepositoryInterface
{
    public function find(int $id): ?Company;

    public function findBySlug(string $slug): ?Company;

    public function findByHrRefId(int $hrRefId): ?Company;

    public function allActiveWithCounts(): Collection;

    public function count(): int;

    public function create(array $attributes): Company;

    public function update(Company $company, array $attributes): Company;

    public function delete(Company $company): bool;
}
