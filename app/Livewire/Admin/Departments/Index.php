<?php

namespace App\Livewire\Admin\Departments;

use App\Repositories\Contracts\DepartmentRepositoryInterface;
use Livewire\Component;

/**
 * Department is org-wide master data, not scoped to a company — see
 * architecture-plan.md §2.5. This form has no Company field.
 */
class Index extends Component
{
    public string $search = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public array $form = [
        'name' => '', 'is_active' => true,
    ];

    public ?int $lockedHrRef = null;

    public ?string $flash = null;

    protected function rules(): array
    {
        return [
            'form.name' => ['required', 'string', 'max:255'],
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
            'name' => '', 'is_active' => true,
        ];
        $this->resetErrorBag();
    }

    public function render(DepartmentRepositoryInterface $departments)
    {
        $term = strtolower(trim($this->search));

        $list = $departments->allActiveWithCounts()
            ->when($term !== '', fn ($c) => $c->filter(fn ($d) => str_contains(strtolower($d->name), $term)))
            ->values();

        return view('livewire.admin.departments.index', [
            'departments' => $list,
        ])->layout('layouts.admin', ['header' => 'Departments']);
    }
}
