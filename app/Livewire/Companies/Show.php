<?php

namespace App\Livewire\Companies;

use App\Models\Company;
use App\Models\Employee;
use App\Repositories\Contracts\DepartmentRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Livewire\Component;
use Livewire\WithPagination;

class Show extends Component
{
    use WithPagination;

    public Company $company;

    public string $tab = 'overview';

    public function mount(Company $company): void
    {
        $this->company = $company;
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
        $this->resetPage();
    }

    public function render(DepartmentRepositoryInterface $departments, EmployeeRepositoryInterface $employees)
    {
        return view('livewire.companies.show', [
            'departments' => $this->tab === 'departments' ? $departments->forCompany($this->company->id) : collect(),
            'employees' => $this->tab === 'employees'
                ? $employees->paginateForDirectory(['company_id' => $this->company->id], 'name', 12)
                : null,
            'departmentCount' => $departments->forCompany($this->company->id)->count(),
            'employeeCount' => Employee::visibleInDirectory()->where('company_id', $this->company->id)->count(),
        ])->layout('layouts.app', ['header' => $this->company->name]);
    }
}
