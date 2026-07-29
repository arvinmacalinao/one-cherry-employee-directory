<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Department;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Support\Collection;

class SearchService
{
    public function __construct(
        protected EmployeeRepositoryInterface $employees,
    ) {}

    /**
     * Grouped, Spotlight-style results for the hero search autocomplete.
     *
     * @return array{people: Collection, companies: Collection, departments: Collection}
     */
    public function globalSearch(string $term, int $limitPerGroup = 5): array
    {
        $term = trim($term);

        if ($term === '') {
            return ['people' => collect(), 'companies' => collect(), 'departments' => collect()];
        }

        return [
            'people' => $this->employees->search($term, $limitPerGroup),
            'companies' => Company::active()->where('name', 'like', "%{$term}%")->limit($limitPerGroup)->get(),
            'departments' => Department::active()->where('name', 'like', "%{$term}%")->limit($limitPerGroup)->get(),
        ];
    }
}
