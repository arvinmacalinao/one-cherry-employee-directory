<?php

namespace App\Livewire\Admin;

use App\Enums\SyncType;
use App\Models\ApiSyncLog;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Services\HrSync\HrSyncService;
use Livewire\Component;
use Livewire\WithPagination;

class Sync extends Component
{
    use WithPagination;

    public bool $syncing = false;

    public ?string $flash = null;

    public function runSync(HrSyncService $syncService): void
    {
        $this->syncing = true;
        $syncService->sync(SyncType::Manual, auth()->user());
        $this->syncing = false;
        $this->flash = 'Sync complete — everything is up to date.';
    }

    /**
     * Reassign every employee from an auto-created stub onto a real target record,
     * then remove the stub. This is how an "Unmapped #id" gets resolved permanently.
     */
    public function mergeUnmapped(string $type, int $stubId, int $targetId): void
    {
        if (! $targetId || $stubId === $targetId) {
            return;
        }

        [$model, $column] = match ($type) {
            'company' => [Company::class, 'company_id'],
            'department' => [Department::class, 'department_id'],
            'designation' => [Designation::class, 'designation_id'],
        };

        $stub = $model::findOrFail($stubId);

        // A stub company can still have child departments/designations pointing at it —
        // resolve those first so deleting the stub never cascades into real data.
        if ($type === 'company' && (Department::where('company_id', $stubId)->exists() || Designation::where('company_id', $stubId)->exists())) {
            $this->flash = null;
            $this->addError('merge', 'This company still has departments or designations attached — resolve those first.');

            return;
        }

        Employee::where($column, $stubId)->update([$column => $targetId]);
        $stub->delete();
        $this->flash = ucfirst($type).' merged and stub record removed.';
    }

    public function render()
    {
        $lastSync = ApiSyncLog::latest('started_at')->first();

        return view('livewire.admin.sync', [
            'lastSync' => $lastSync,
            'unmappedCompanies' => Company::unmapped()->withCount('employees')->get(),
            'unmappedDepartments' => Department::unmapped()->with('company')->withCount('employees')->get(),
            'unmappedDesignations' => Designation::unmapped()->with('company')->withCount('employees')->get(),
            'companyOptions' => Company::active()->orderBy('name')->get(),
            'departmentOptions' => Department::active()->orderBy('name')->get(),
            'designationOptions' => Designation::active()->orderBy('name')->get(),
            'history' => ApiSyncLog::with('triggeredBy')->latest('started_at')->paginate(10),
        ])->layout('layouts.admin', ['header' => 'API Sync']);
    }
}
