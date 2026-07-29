<?php

namespace App\Livewire\Admin\Designations;

use App\Models\Company;
use App\Repositories\Contracts\DesignationRepositoryInterface;
use Livewire\Component;

class Index extends Component
{
    public string $search = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public array $form = [
        'name' => '', 'company_id' => '', 'is_active' => true,
    ];

    public ?int $lockedHrRef = null;

    public ?string $flash = null;

    protected function rules(): array
    {
        return [
            'form.name' => ['required', 'string', 'max:255'],
            'form.company_id' => ['required', 'exists:companies,id'],
        ];
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $id, DesignationRepositoryInterface $designations): void
    {
        $designation = $designations->find($id);
        abort_if(! $designation, 404);

        $this->editingId = $designation->id;
        $this->lockedHrRef = $designation->hr_ref_id;
        $this->form = [
            'name' => $designation->name,
            'company_id' => (string) $designation->company_id,
            'is_active' => $designation->is_active,
        ];
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function save(DesignationRepositoryInterface $designations): void
    {
        $validated = $this->validate()['form'];

        if ($this->lockedHrRef) {
            unset($validated['name']);
        }

        // Saving is how an Admin acknowledges an auto-created record from HR sync.
        $validated['needs_review'] = false;

        if ($this->editingId) {
            $designations->update($designations->find($this->editingId), $validated);
            $this->flash = 'Designation updated';
        } else {
            $designations->create($validated);
            $this->flash = 'Designation created';
        }

        $this->closeForm();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->lockedHrRef = null;
        $this->form = [
            'name' => '', 'company_id' => '', 'is_active' => true,
        ];
        $this->resetErrorBag();
    }

    public function render()
    {
        $term = strtolower(trim($this->search));

        $designations = \App\Models\Designation::with('company')
            ->withCount(['employees' => fn ($q) => $q->visibleInDirectory()])
            ->when($term !== '', fn ($q) => $q->where('name', 'like', "%{$term}%"))
            ->orderBy('name')
            ->get();

        return view('livewire.admin.designations.index', [
            'designations' => $designations,
            'companyOptions' => Company::active()->orderBy('name')->get(),
        ])->layout('layouts.admin', ['header' => 'Designations']);
    }
}
