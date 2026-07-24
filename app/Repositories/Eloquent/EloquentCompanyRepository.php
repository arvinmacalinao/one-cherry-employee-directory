<?php

namespace App\Repositories\Eloquent;

use App\Models\Company;
use App\Repositories\Contracts\CompanyRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentCompanyRepository implements CompanyRepositoryInterface
{
    public function find(int $id): ?Company
    {
        return Company::find($id);
    }

    public function findBySlug(string $slug): ?Company
    {
        return Company::where('slug', $slug)->first();
    }

    public function findByHrRefId(int $hrRefId): ?Company
    {
        return Company::where('hr_ref_id', $hrRefId)->first();
    }

    public function allActiveWithCounts(): Collection
    {
        return Company::active()
            ->withCount(['employees' => fn ($q) => $q->visibleInDirectory()])
            ->orderBy('name')
            ->get();
    }

    public function count(): int
    {
        return Company::active()->count();
    }

    public function create(array $attributes): Company
    {
        return Company::create($attributes);
    }

    public function update(Company $company, array $attributes): Company
    {
        $company->update($attributes);

        return $company->refresh();
    }

    public function delete(Company $company): bool
    {
        return (bool) $company->delete();
    }
}
