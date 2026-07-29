<?php

namespace App\Livewire\Departments;

use App\Repositories\Contracts\DepartmentRepositoryInterface;
use Livewire\Component;

/**
 * Department is org-wide master data, not per-company — see architecture-plan.md
 * §2.5. There's no company filter here since a department isn't "at" a company.
 */
class Index extends Component
{
    public string $search = '';

    public function render(DepartmentRepositoryInterface $departments)
    {
        $term = strtolower(trim($this->search));

        $list = $departments->allActiveWithCounts()
            ->when($term !== '', fn ($collection) => $collection->filter(
                fn ($department) => str_contains(strtolower($department->name), $term)
            ))
            ->values();

        return view('livewire.departments.index', [
            'departments' => $list,
        ])->layout('layouts.app', ['header' => 'Departments']);
    }
}
