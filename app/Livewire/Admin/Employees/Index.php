<?php

namespace App\Livewire\Admin\Employees;

use App\Enums\EmployeeSource;
use App\Enums\EmploymentStatus;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\OfficeLocation;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\EmployeeProfileService;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $companyFilter = '';

    public string $statusFilter = '';

    public bool $showForm = false;

    public string $activeTab = 'personal';

    public ?int $editingId = null;

    public bool $isSynced = false;

    public array $form = [];

    public ?string $flash = null;

    protected function defaultForm(): array
    {
        return [
            'employee_id' => '', 'first_name' => '', 'middle_name' => '', 'last_name' => '', 'email' => '',
            'company_id' => '', 'department_id' => '', 'designation_id' => '', 'immediate_supervisor_id' => '',
            'employment_status' => 'active', 'date_hired' => '',
            'suffix' => '', 'nickname' => '', 'gender' => '', 'birthday' => '', 'name_pronunciation' => '',
            'personal_email' => '', 'mobile_number' => '', 'viber_number' => '', 'telephone' => '', 'local_extension' => '',
            'office_seat' => '', 'office_location_id' => '',
            'about_me' => '', 'facebook_url' => '', 'linkedin_url' => '',
            'emergency_contact_name' => '', 'emergency_contact_relationship' => '', 'emergency_contact_phone' => '',
            'skills' => '',
        ];
    }

    protected function rules(): array
    {
        // Department, middle name, date hired, and supervisor are Admin-managed for
        // every employee regardless of source — the real HR API doesn't expose them
        // at all (see architecture-plan.md §2.4), so they're never HR-locked.
        $rules = [
            'form.middle_name' => ['nullable', 'string', 'max:255'],
            'form.department_id' => ['nullable', 'exists:departments,id'],
            'form.immediate_supervisor_id' => ['nullable', 'exists:employees,id'],
            'form.date_hired' => ['nullable', 'date'],
            'form.personal_email' => ['nullable', 'email'],
            'form.mobile_number' => ['nullable', 'string', 'max:30'],
            'form.viber_number' => ['nullable', 'string', 'max:30'],
            'form.telephone' => ['nullable', 'string', 'max:30'],
            'form.local_extension' => ['nullable', 'string', 'max:10'],
            'form.office_seat' => ['nullable', 'string', 'max:255'],
            'form.office_location_id' => ['nullable', 'exists:office_locations,id'],
            'form.suffix' => ['nullable', 'string', 'max:255'],
            'form.nickname' => ['nullable', 'string', 'max:255'],
            'form.gender' => ['nullable', 'string', 'max:255'],
            'form.birthday' => ['nullable', 'date'],
            'form.name_pronunciation' => ['nullable', 'string', 'max:255'],
            'form.about_me' => ['nullable', 'string'],
            'form.facebook_url' => ['nullable', 'string', 'max:255'],
            'form.linkedin_url' => ['nullable', 'string', 'max:255'],
            'form.emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'form.emergency_contact_relationship' => ['nullable', 'string', 'max:255'],
            'form.emergency_contact_phone' => ['nullable', 'string', 'max:30'],
            'form.skills' => ['nullable', 'string'],
        ];

        if (! $this->isSynced) {
            $rules += [
                'form.first_name' => ['required', 'string', 'max:255'],
                'form.last_name' => ['required', 'string', 'max:255'],
                'form.email' => ['required', 'email', 'max:255', 'unique:employees,email,'.$this->editingId],
                'form.company_id' => ['required', 'exists:companies,id'],
                'form.designation_id' => ['required', 'exists:designations,id'],
                'form.employment_status' => ['required'],
            ];
        }

        return $rules;
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

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->isSynced = false;
        $this->showForm = true;
    }

    public function openEdit(int $id): void
    {
        $employee = Employee::with('profile')->findOrFail($id);
        $profile = $employee->profile;

        $this->editingId = $employee->id;
        $this->isSynced = $employee->source === EmployeeSource::HrSync;
        $this->activeTab = 'personal';

        $this->form = [
            'employee_id' => $employee->employee_id,
            'first_name' => $employee->first_name,
            'middle_name' => $employee->middle_name,
            'last_name' => $employee->last_name,
            'email' => $employee->email,
            'company_id' => (string) $employee->company_id,
            'department_id' => (string) $employee->department_id,
            'designation_id' => (string) $employee->designation_id,
            'immediate_supervisor_id' => $employee->immediate_supervisor_id ? (string) $employee->immediate_supervisor_id : '',
            'employment_status' => $employee->employment_status->value,
            'date_hired' => $employee->date_hired?->format('Y-m-d'),
            'suffix' => $profile?->suffix,
            'nickname' => $profile?->nickname,
            'gender' => $profile?->gender,
            'birthday' => $profile?->birthday?->format('Y-m-d'),
            'name_pronunciation' => $profile?->name_pronunciation,
            'personal_email' => $profile?->personal_email,
            'mobile_number' => $profile?->mobile_number,
            'viber_number' => $profile?->viber_number,
            'telephone' => $profile?->telephone,
            'local_extension' => $profile?->local_extension,
            'office_seat' => $profile?->office_seat,
            'office_location_id' => $profile?->office_location_id ? (string) $profile->office_location_id : '',
            'about_me' => $profile?->about_me,
            'facebook_url' => $profile?->facebook_url,
            'linkedin_url' => $profile?->linkedin_url,
            'emergency_contact_name' => $profile?->emergency_contact_name,
            'emergency_contact_relationship' => $profile?->emergency_contact_relationship,
            'emergency_contact_phone' => $profile?->emergency_contact_phone,
            'skills' => $employee->skills->pluck('name')->implode(', '),
        ];

        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function save(EmployeeRepositoryInterface $employees, EmployeeProfileService $profileService): void
    {
        $validated = $this->validate()['form'];

        $profileFields = collect($validated)->only([
            'suffix', 'nickname', 'gender', 'birthday', 'name_pronunciation',
            'personal_email', 'mobile_number', 'viber_number', 'telephone', 'local_extension',
            'office_seat', 'office_location_id', 'about_me', 'facebook_url', 'linkedin_url',
            'emergency_contact_name', 'emergency_contact_relationship', 'emergency_contact_phone',
        ])->map(fn ($v) => $v === '' ? null : $v)->all();

        // Admin-managed regardless of source — HR doesn't provide any of these.
        $adminManagedFields = collect($validated)->only([
            'middle_name', 'department_id', 'immediate_supervisor_id', 'date_hired',
        ])->map(fn ($v) => $v === '' ? null : $v)->all();

        if ($this->editingId) {
            $employee = $employees->find($this->editingId);
            $employees->update($employee, $adminManagedFields);

            // The rest of the employees-table fields are HR-controlled once synced — never touch them here.
            if (! $this->isSynced) {
                $employees->update($employee, collect($validated)->only([
                    'first_name', 'last_name', 'email', 'company_id', 'designation_id', 'employment_status',
                ])->map(fn ($v) => $v === '' ? null : $v)->all());
            }

            $this->flash = 'Employee updated';
        } else {
            $employee = $employees->create([
                'employee_id' => 'MAN-'.Str::upper(Str::random(6)),
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'company_id' => $validated['company_id'],
                'designation_id' => $validated['designation_id'],
                'employment_status' => $validated['employment_status'],
                'source' => EmployeeSource::Manual,
                ...$adminManagedFields,
            ]);

            $this->flash = 'Employee created';
        }

        $profileService->updateProfile($employee, $profileFields);
        $profileService->syncSkills($employee, array_filter(array_map('trim', explode(',', $validated['skills'] ?? ''))));

        $this->closeForm();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->isSynced = false;
        $this->activeTab = 'personal';
        $this->form = $this->defaultForm();
        $this->resetErrorBag();
    }

    public function render(EmployeeRepositoryInterface $employees)
    {
        $query = Employee::with(['company', 'department', 'designation'])
            ->when($this->search !== '', function ($q) {
                $term = $this->search;
                $q->where(fn ($w) => $w->where('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('employee_id', 'like', "%{$term}%"));
            })
            ->when($this->companyFilter !== '', fn ($q) => $q->where('company_id', $this->companyFilter))
            ->when($this->statusFilter !== '', fn ($q) => $q->where('employment_status', $this->statusFilter))
            ->orderByDesc('created_at');

        $companyId = $this->form['company_id'] ?? null;

        return view('livewire.admin.employees.index', [
            'employees' => $query->paginate(15),
            'companyOptions' => Company::active()->orderBy('name')->get(),
            'departmentOptions' => $companyId ? Department::active()->where('company_id', $companyId)->orderBy('name')->get() : collect(),
            'designationOptions' => $companyId ? Designation::active()->where('company_id', $companyId)->orderBy('name')->get() : collect(),
            'supervisorOptions' => $companyId ? Employee::visibleInDirectory()->where('company_id', $companyId)->orderBy('last_name')->get() : collect(),
            'officeOptions' => OfficeLocation::active()->orderBy('name')->get(),
        ])->layout('layouts.admin', ['header' => 'Employees']);
    }
}
