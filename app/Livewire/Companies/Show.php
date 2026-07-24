<?php

namespace App\Livewire\Companies;

use App\Models\Company;
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
        $companyEmployees = $this->tab === 'orgchart'
            ? \App\Models\Employee::visibleInDirectory()
                ->where('company_id', $this->company->id)
                ->with(['designation', 'department'])
                ->get()
                ->keyBy('id')
            : collect();

        return view('livewire.companies.show', [
            'departments' => $this->tab === 'departments' ? $departments->forCompany($this->company->id) : collect(),
            'employees' => $this->tab === 'employees'
                ? $employees->paginateForDirectory(['company_id' => $this->company->id], 'name', 12)
                : null,
            'orgRoots' => $companyEmployees->filter(
                fn ($e) => ! $e->immediate_supervisor_id || ! $companyEmployees->has($e->immediate_supervisor_id)
            )->values(),
            'reportsMap' => $companyEmployees->groupBy('immediate_supervisor_id'),
            'departmentCount' => $departments->forCompany($this->company->id)->count(),
            'employeeCount' => \App\Models\Employee::visibleInDirectory()->where('company_id', $this->company->id)->count(),
        ])->layout('layouts.app', ['header' => $this->company->name]);
    }
}
