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
     * Dismiss the flag without merging — the name HR sent is legitimately new/correct,
     * it just hasn't had its branding/description/hierarchy filled in yet.
     */
    public function markReviewed(string $type, int $id): void
    {
        $model = $type === 'company' ? Company::class : Designation::class;
        $model::whereKey($id)->update(['needs_review' => false]);
        $this->flash = ucfirst($type).' marked as reviewed.';
    }

    /**
     * Reassign every employee from a duplicate/auto-created record onto a real target
     * record, then remove the duplicate. Mainly useful now for Companies/Designations,
     * since HR sends names rather than stable IDs — a typo or renaming on HR's side can
     * produce a near-duplicate that this merges away.
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
        $this->flash = ucfirst($type).' merged and duplicate record removed.';
    }

    public function render()
    {
        $lastSync = ApiSyncLog::latest('started_at')->first();

        return view('livewire.admin.sync', [
            'lastSync' => $lastSync,
            // Department is no longer HR-synced at all (the API doesn't expose it — see
            // architecture-plan.md §2.4), so it never gets flagged here, only Company/Designation.
            'needsReviewCompanies' => Company::needsReview()->withCount('employees')->get(),
            'needsReviewDesignations' => Designation::needsReview()->with('company')->withCount('employees')->get(),
            'companyOptions' => Company::active()->orderBy('name')->get(),
            'designationOptions' => Designation::active()->orderBy('name')->get(),
            'history' => ApiSyncLog::with('triggeredBy')->latest('started_at')->paginate(10),
        ])->layout('layouts.admin', ['header' => 'API Sync']);
    }
}
