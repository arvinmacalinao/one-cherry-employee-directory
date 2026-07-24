<?php

namespace App\Livewire\Directory;

use App\Repositories\Contracts\CompanyRepositoryInterface;
use App\Repositories\Contracts\DepartmentRepositoryInterface;
use App\Services\EmployeeDirectoryService;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $company = '';

    #[Url]
    public string $department = '';

    #[Url]
    public string $status = '';

    public string $sort = 'newest';

    public function updating($property): void
    {
        if (in_array($property, ['search', 'company', 'department', 'status', 'sort'])) {
            $this->resetPage();
        }
    }

    public function render(EmployeeDirectoryService $directory, CompanyRepositoryInterface $companies, DepartmentRepositoryInterface $departments)
    {
        $employees = $directory->browse([
            'search' => $this->search,
            'company_id' => $this->company,
            'department_id' => $this->department,
            'employment_status' => $this->status,
        ], $this->sort);

        return view('livewire.directory.index', [
            'employees' => $employees,
            'companyOptions' => $companies->allActiveWithCounts(),
            'departmentOptions' => $departments->allActiveWithCounts(),
        ])->layout('layouts.app', ['header' => 'Employee Directory']);
    }
}
