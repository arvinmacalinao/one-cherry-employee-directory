<?php

namespace App\Livewire\Admin\Companies;

use App\Models\Company;
use App\Repositories\Contracts\CompanyRepositoryInterface;
use Livewire\Component;

class Index extends Component
{
    public string $search = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public array $form = [
        'name' => '', 'description' => '', 'address' => '', 'phone' => '',
        'email' => '', 'website' => '', 'color_theme' => '#790002', 'is_active' => true,
    ];

    public ?int $lockedHrRef = null;

    public ?string $flash = null;

    protected function rules(): array
    {
        return [
            'form.name' => ['required', 'string', 'max:255'],
            'form.description' => ['nullable', 'string'],
            'form.address' => ['nullable', 'string', 'max:255'],
            'form.phone' => ['nullable', 'string', 'max:30'],
            'form.email' => ['nullable', 'email', 'max:255'],
            'form.website' => ['nullable', 'string', 'max:255'],
            'form.color_theme' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $id, CompanyRepositoryInterface $companies): void
    {
        $company = $companies->find($id);
        abort_if(! $company, 404);

        $this->editingId = $company->id;
        $this->lockedHrRef = $company->hr_ref_id;
        $this->form = [
            'name' => $company->name,
            'description' => $company->description,
            'address' => $company->address,
            'phone' => $company->phone,
            'email' => $company->email,
            'website' => $company->website,
            'color_theme' => $company->color_theme ?? '#790002',
            'is_active' => $company->is_active,
        ];
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function save(CompanyRepositoryInterface $companies): void
    {
        $validated = $this->validate()['form'];

        // Identity (name) is HR-controlled once linked — never overwrite it from this form.
        if ($this->lockedHrRef) {
            unset($validated['name']);
        }

        if ($this->editingId) {
            $companies->update($companies->find($this->editingId), $validated);
            $this->flash = 'Company updated';
        } else {
            $validated['slug'] = \Illuminate\Support\Str::slug($validated['name']).'-'.\Illuminate\Support\Str::random(4);
            $companies->create($validated);
            $this->flash = 'Company created';
        }

        $this->closeForm();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->lockedHrRef = null;
        $this->form = [
            'name' => '', 'description' => '', 'address' => '', 'phone' => '',
            'email' => '', 'website' => '', 'color_theme' => '#790002', 'is_active' => true,
        ];
        $this->resetErrorBag();
    }

    public function render()
    {
        $term = strtolower(trim($this->search));

        $companies = Company::withCount(['employees' => fn ($q) => $q->visibleInDirectory()])
            ->when($term !== '', fn ($q) => $q->where('name', 'like', "%{$term}%"))
            ->orderBy('name')
            ->get();

        return view('livewire.admin.companies.index', [
            'companies' => $companies,
        ])->layout('layouts.admin', ['header' => 'Companies']);
    }
}
