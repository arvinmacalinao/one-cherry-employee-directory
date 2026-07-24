<?php

namespace App\Livewire\Admin\Departments;

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Repositories\Contracts\DepartmentRepositoryInterface;
use Livewire\Component;

class Index extends Component
{
    public string $search = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public array $form = [
        'name' => '', 'company_id' => '', 'department_head_id' => '', 'description' => '', 'is_active' => true,
    ];

    public ?int $lockedHrRef = null;

    public ?string $flash = null;

    protected function rules(): array
    {
        return [
            'form.name' => ['required', 'string', 'max:255'],
            'form.company_id' => ['required', 'exists:companies,id'],
            'form.department_head_id' => ['nullable', 'exists:employees,id'],
            'form.description' => ['nullable', 'string'],
        ];
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $id, DepartmentRepositoryInterface $departments): void
    {
        $department = $departments->find($id);
        abort_if(! $department, 404);

        $this->editingId = $department->id;
        $this->lockedHrRef = $department->hr_ref_id;
        $this->form = [
            'name' => $department->name,
            'company_id' => (string) $department->company_id,
            'department_head_id' => $department->department_head_id ? (string) $department->department_head_id : '',
            'description' => $department->description,
            'is_active' => $department->is_active,
        ];
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function save(DepartmentRepositoryInterface $departments): void
    {
        $validated = $this->validate()['form'];

        if ($this->lockedHrRef) {
            unset($validated['name']);
        }

        $validated['department_head_id'] = $validated['department_head_id'] ?: null;
        $validated['needs_review'] = false;

        if ($this->editingId) {
            $departments->update($departments->find($this->editingId), $validated);
            $this->flash = 'Department updated';
        } else {
            $departments->create($validated);
            $this->flash = 'Department created';
        }

        $this->closeForm();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->lockedHrRef = null;
        $this->form = [
            'name' => '', 'company_id' => '', 'department_head_id' => '', 'description' => '', 'is_active' => true,
        ];
        $this->resetErrorBag();
    }

    public function employeesForCompany(): \Illuminate\Support\Collection
    {
        if (! $this->form['company_id']) {
            return collect();
        }

        return Employee::visibleInDirectory()->where('company_id', $this->form['company_id'])->orderBy('last_name')->get();
    }

    public function render(DepartmentRepositoryInterface $departments)
    {
        $term = strtolower(trim($this->search));

        $list = $departments->allActiveWithCounts()
            ->when($term !== '', fn ($c) => $c->filter(fn ($d) => str_contains(strtolower($d->name), $term)))
            ->values();

        return view('livewire.admin.departments.index', [
            'departments' => $list,
            'companyOptions' => Company::active()->orderBy('name')->get(),
            'headOptions' => $this->employeesForCompany(),
        ])->layout('layouts.admin', ['header' => 'Departments']);
    }
}
