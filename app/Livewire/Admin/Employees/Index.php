<?php

namespace App\Livewire\Admin\Employees;

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeStatus;
use App\Models\OfficeLocation;
use App\Services\EmployeeProfileService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * Every employee row originates from HR sync now — there is no manual employee
 * creation. An Admin may only edit the directory-owned fields (photo, about,
 * Viber, office, birthday); every HR-owned field is shown read-only for context.
 * See architecture-plan.md §2.4, §5, §7.
 */
class Index extends Component
{
    use WithFileUploads, WithPagination;

    public string $search = '';

    public string $companyFilter = '';

    public string $statusFilter = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public ?Employee $editingEmployee = null;

    public array $form = [];

    public $photo = null;

    public ?string $flash = null;

    protected function defaultForm(): array
    {
        return [
            'birthday' => '', 'viber_number' => '', 'office_location_id' => '', 'about_me' => '',
        ];
    }

    protected function rules(): array
    {
        return [
            'photo' => ['nullable', 'image', 'max:5120'],
            'form.birthday' => ['nullable', 'date'],
            'form.viber_number' => ['nullable', 'string', 'max:30'],
            'form.office_location_id' => ['nullable', 'exists:office_locations,id'],
            'form.about_me' => ['nullable', 'string'],
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCompanyFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function openEdit(int $id): void
    {
        $employee = Employee::with(['company', 'department', 'designation', 'status', 'supervisor', 'profile'])->findOrFail($id);
        $profile = $employee->profile;

        $this->editingId = $employee->id;
        $this->editingEmployee = $employee;

        $this->form = [
            'birthday' => $profile?->birthday?->format('Y-m-d'),
            'viber_number' => $profile?->viber_number,
            'office_location_id' => $profile?->office_location_id ? (string) $profile->office_location_id : '',
            'about_me' => $profile?->about_me,
        ];

        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function removePhoto(): void
    {
        $this->editingEmployee?->clearMediaCollection('photo');
        $this->photo = null;
    }

    public function save(EmployeeProfileService $profileService): void
    {
        $validated = $this->validate()['form'];
        $employee = Employee::findOrFail($this->editingId);

        $safeAttributes = collect($validated)->map(fn ($v) => $v === '' ? null : $v)->all();
        $profileService->updateProfile($employee, $safeAttributes);

        if ($this->photo) {
            $employee->addMedia($this->photo->getRealPath())
                ->usingName($this->photo->getClientOriginalName())
                ->usingFileName($this->photo->getClientOriginalName())
                ->toMediaCollection('photo');
        }

        $this->flash = 'Employee updated';
        $this->closeForm();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->editingEmployee = null;
        $this->form = $this->defaultForm();
        $this->photo = null;
        $this->resetErrorBag();
    }

    public function render()
    {
        $query = Employee::with(['company', 'department', 'designation', 'status'])
            ->when($this->search !== '', function ($q) {
                $term = $this->search;
                $q->where(fn ($w) => $w->where('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('employee_id', 'like', "%{$term}%"));
            })
            ->when($this->companyFilter !== '', fn ($q) => $q->where('company_id', $this->companyFilter))
            ->when($this->statusFilter !== '', fn ($q) => $q->where('employee_status_id', $this->statusFilter))
            ->orderByDesc('created_at');

        return view('livewire.admin.employees.index', [
            'employees' => $query->paginate(15),
            'companyOptions' => Company::active()->orderBy('name')->get(),
            'statusOptions' => EmployeeStatus::orderBy('name')->get(),
            'officeOptions' => OfficeLocation::active()->orderBy('name')->get(),
        ])->layout('layouts.admin', ['header' => 'Employees']);
    }
}
