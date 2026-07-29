<?php

namespace App\Livewire\Admin\Companies;

use App\Models\Company;
use App\Repositories\Contracts\CompanyRepositoryInterface;
use Livewire\Component;
use Livewire\WithFileUploads;

class Index extends Component
{
    use WithFileUploads;

    public string $search = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public ?Company $editingCompany = null;

    public array $form = [
        'name' => '', 'address' => '', 'phone' => '',
        'email' => '', 'website' => '', 'is_active' => true,
    ];

    public $logo = null;

    public ?int $lockedHrRef = null;

    public ?string $flash = null;

    protected function rules(): array
    {
        return [
            'logo' => ['nullable', 'image', 'max:2048'],
            'form.name' => ['required', 'string', 'max:255'],
            'form.address' => ['nullable', 'string', 'max:255'],
            'form.phone' => ['nullable', 'string', 'max:30'],
            'form.email' => ['nullable', 'email', 'max:255'],
            'form.website' => ['nullable', 'string', 'max:255'],
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
        $this->editingCompany = $company;
        $this->lockedHrRef = $company->hr_ref_id;
        $this->form = [
            'name' => $company->name,
            'address' => $company->address,
            'phone' => $company->phone,
            'email' => $company->email,
            'website' => $company->website,
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

        // Identity (name) is HR-owned once linked to a numeric hr_ref_id — never overwrite it
        // from this form. See architecture-plan.md §2.5, §7.
        if ($this->lockedHrRef) {
            unset($validated['name']);
        }

        // Saving is how an Admin acknowledges an auto-created record from HR sync.
        $validated['needs_review'] = false;

        if ($this->editingId) {
            $company = $companies->find($this->editingId);
            $companies->update($company, $validated);
        } else {
            $validated['slug'] = \Illuminate\Support\Str::slug($validated['name']).'-'.\Illuminate\Support\Str::random(4);
            $company = $companies->create($validated);
        }

        if ($this->logo) {
            $company->addMedia($this->logo->getRealPath())
                ->usingFileName($this->logo->getClientOriginalName())
                ->toMediaCollection('logo');
        }

        $this->flash = $this->editingId ? 'Company updated' : 'Company created';
        $this->closeForm();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->editingCompany = null;
        $this->lockedHrRef = null;
        $this->form = [
            'name' => '', 'address' => '', 'phone' => '',
            'email' => '', 'website' => '', 'is_active' => true,
        ];
        $this->logo = null;
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
