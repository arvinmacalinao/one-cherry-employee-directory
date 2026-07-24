<?php

namespace App\Livewire\Departments;

use App\Repositories\Contracts\CompanyRepositoryInterface;
use App\Repositories\Contracts\DepartmentRepositoryInterface;
use Livewire\Component;

class Index extends Component
{
    public string $search = '';

    public string $company = '';

    public function render(DepartmentRepositoryInterface $departments, CompanyRepositoryInterface $companies)
    {
        $term = strtolower(trim($this->search));

        $list = $departments->allActiveWithCounts()
            ->when($term !== '', fn ($collection) => $collection->filter(
                fn ($department) => str_contains(strtolower($department->name), $term)
            ))
            ->when($this->company !== '', fn ($collection) => $collection->filter(
                fn ($department) => (string) $department->company_id === $this->company
            ))
            ->values();

        return view('livewire.departments.index', [
            'departments' => $list,
            'companyOptions' => $companies->allActiveWithCounts(),
        ])->layout('layouts.app', ['header' => 'Departments']);
    }
}
