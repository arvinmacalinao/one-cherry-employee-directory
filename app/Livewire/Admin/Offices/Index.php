<?php

namespace App\Livewire\Admin\Offices;

use App\Models\Company;
use App\Repositories\Contracts\OfficeLocationRepositoryInterface;
use Livewire\Component;

class Index extends Component
{
    public string $search = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public array $form = [
        'name' => '', 'address' => '', 'city' => '', 'country' => 'Philippines',
        'phone' => '', 'company_id' => '', 'is_active' => true,
    ];

    public ?string $flash = null;

    protected function rules(): array
    {
        return [
            'form.name' => ['required', 'string', 'max:255'],
            'form.address' => ['nullable', 'string', 'max:255'],
            'form.city' => ['nullable', 'string', 'max:255'],
            'form.country' => ['nullable', 'string', 'max:255'],
            'form.phone' => ['nullable', 'string', 'max:30'],
            'form.company_id' => ['nullable', 'exists:companies,id'],
        ];
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $id, OfficeLocationRepositoryInterface $offices): void
    {
        $office = $offices->find($id);
        abort_if(! $office, 404);

        $this->editingId = $office->id;
        $this->form = [
            'name' => $office->name,
            'address' => $office->address,
            'city' => $office->city,
            'country' => $office->country,
            'phone' => $office->phone,
            'company_id' => $office->company_id ? (string) $office->company_id : '',
            'is_active' => $office->is_active,
        ];
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function save(OfficeLocationRepositoryInterface $offices): void
    {
        $validated = $this->validate()['form'];
        $validated['company_id'] = $validated['company_id'] ?: null;

        if ($this->editingId) {
            $offices->update($offices->find($this->editingId), $validated);
            $this->flash = 'Office location updated';
        } else {
            $offices->create($validated);
            $this->flash = 'Office location created';
        }

        $this->closeForm();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->form = [
            'name' => '', 'address' => '', 'city' => '', 'country' => 'Philippines',
            'phone' => '', 'company_id' => '', 'is_active' => true,
        ];
        $this->resetErrorBag();
    }

    public function render(OfficeLocationRepositoryInterface $offices)
    {
        $term = strtolower(trim($this->search));

        $list = \App\Models\OfficeLocation::with('company')
            ->when($term !== '', fn ($q) => $q->where('name', 'like', "%{$term}%"))
            ->orderBy('name')
            ->get();

        return view('livewire.admin.offices.index', [
            'offices' => $list,
            'companyOptions' => Company::active()->orderBy('name')->get(),
        ])->layout('layouts.admin', ['header' => 'Office Locations']);
    }
}
