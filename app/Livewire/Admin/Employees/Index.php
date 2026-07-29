<?php

namespace App\Livewire\Admin\Employees;

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeStatus;
use App\Models\OfficeLocation;
use App\Services\EmployeeCsvService;
use App\Services\EmployeeProfileService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * Every employee row originates from HR sync now — there is no manual employee
 * creation. An Admin may only edit the directory-owned fields (photo, about,
 * Viber, telephone, office, birthday); every HR-owned field is shown read-only
 * for context — except email, which HR frequently doesn't provide at all.
 * Email is a fallback-editable field: an Admin can set it, and it sticks unless
 * HR later sends a real value of its own, which then wins again on the next
 * sync (see HrSyncService::upsertEmployee()). See architecture-plan.md §2.4, §5, §7.
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

    public bool $showImportModal = false;

    public $importFile = null;

    /** @var array{rowsProcessed: int, employeesUpdated: int, warnings: array}|null */
    public ?array $importSummary = null;

    protected function defaultForm(): array
    {
        return [
            'email' => '', 'birthday' => '', 'viber_number' => '', 'telephone' => '', 'local_extension' => '',
            'office_location_id' => '', 'about_me' => '',
        ];
    }

    protected function rules(): array
    {
        return [
            'photo' => ['nullable', 'image', 'max:5120'],
            'form.email' => ['nullable', 'email', 'max:255', 'unique:employees,email,'.$this->editingId],
            'form.birthday' => ['nullable', 'date'],
            'form.viber_number' => ['nullable', 'string', 'max:30'],
            'form.telephone' => ['nullable', 'string', 'max:30'],
            'form.local_extension' => ['nullable', 'string', 'max:10'],
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
            'email' => $employee->email,
            'birthday' => $profile?->birthday?->format('Y-m-d'),
            'viber_number' => $profile?->viber_number,
            'telephone' => $profile?->telephone,
            'local_extension' => $profile?->local_extension,
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

        // Email lives on `employees`, not `employee_profiles` — it's a fallback-editable
        // HR field, not a directory-owned one, so it doesn't go through
        // EmployeeProfileService (which only ever writes employee_profiles columns).
        $employee->update(['email' => $safeAttributes['email']]);
        unset($safeAttributes['email']);

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

    /**
     * Exports whatever the current search/company filters are showing — leave
     * both blank to export everyone. Reference columns (name/company/department/
     * designation) are included for the Admin's own orientation only; re-importing
     * this exact file only ever reads the editable columns back. See EmployeeCsvService.
     */
    public function exportCsv(EmployeeCsvService $csv)
    {
        $rows = $csv->exportRows([
            'search' => $this->search,
            'company_id' => $this->companyFilter,
        ]);

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, EmployeeCsvService::HEADERS);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, 'employees-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function openImportModal(): void
    {
        $this->importSummary = null;
        $this->showImportModal = true;
    }

    public function closeImportModal(): void
    {
        $this->showImportModal = false;
        $this->importFile = null;
        $this->importSummary = null;
        $this->resetErrorBag();
    }

    public function runImport(EmployeeCsvService $csv): void
    {
        $this->validate(['importFile' => ['required', 'file', 'mimes:csv,txt', 'max:10240']]);

        $result = $csv->import($this->importFile, Auth::user());

        $this->importSummary = [
            'rowsProcessed' => $result->rowsProcessed,
            'employeesUpdated' => $result->employeesUpdated,
            'warnings' => $result->warnings,
        ];
        $this->importFile = null;

        if ($result->employeesUpdated > 0) {
            $this->flash = "Import complete — updated {$result->employeesUpdated} employee".($result->employeesUpdated === 1 ? '' : 's').'.';
        }
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
