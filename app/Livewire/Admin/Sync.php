<?php

namespace App\Livewire\Admin;

use App\Enums\SyncType;
use App\Models\ApiSyncLog;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Setting;
use App\Services\HrSync\DTOs\SyncPreviewResult;
use App\Services\HrSync\HrSyncService;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Sync is the application's core feature — see architecture-plan.md §2.5. The
 * very first live sync is gated behind Sync Preview: the scheduler stays off
 * (routes/console.php) and "Sync Now" doesn't exist yet — only "Generate
 * Preview", then "Confirm & Run First Sync" once a preview has been reviewed.
 * After that, the flag is permanent and normal Sync Now / scheduled runs apply.
 */
class Sync extends Component
{
    use WithPagination;

    public bool $syncing = false;

    public bool $previewing = false;

    public ?SyncPreviewResult $preview = null;

    public ?string $flash = null;

    public function generatePreview(HrSyncService $syncService): void
    {
        $this->previewing = true;
        $this->preview = $syncService->preview();
        $this->previewing = false;
    }

    public function confirmFirstSync(HrSyncService $syncService): void
    {
        $this->runSync($syncService);
        Setting::set('hr_first_sync_completed_at', now()->toDateTimeString());
        $this->preview = null;
    }

    public function runSync(HrSyncService $syncService): void
    {
        $this->syncing = true;
        $syncService->sync(SyncType::Manual, auth()->user());
        $this->syncing = false;
        $this->flash = 'Sync complete — everything is up to date.';
    }

    /**
     * Dismiss the flag without merging — the name/id HR sent is legitimately new/correct,
     * it just hasn't had its branding filled in yet.
     */
    public function markReviewed(string $type, int $id): void
    {
        $model = $this->modelFor($type);
        $model::whereKey($id)->update(['needs_review' => false]);
        $this->flash = ucfirst($type).' marked as reviewed.';
    }

    /**
     * Clears needs_review in bulk for every flagged record of a given type (or
     * every type when $type is null) — for when a backlog builds up (e.g. right
     * after the department/designation company-scoping fix) and reviewing each
     * one individually isn't practical.
     */
    public function markAllReviewed(?string $type = null): void
    {
        $types = $type ? [$type] : ['company', 'department', 'designation'];
        $count = 0;

        foreach ($types as $t) {
            $model = $this->modelFor($t);
            $count += $model::needsReview()->count();
            $model::needsReview()->update(['needs_review' => false]);
        }

        $this->flash = $count === 0
            ? 'Nothing to review.'
            : "Marked {$count} record".($count === 1 ? '' : 's').' as reviewed.';
    }

    protected function modelFor(string $type): string
    {
        return match ($type) {
            'company' => Company::class,
            'department' => Department::class,
            'designation' => Designation::class,
        };
    }

    /**
     * Reassign every employee from a duplicate/auto-created record onto a real target
     * record, then remove the duplicate — mainly useful when HR sends a near-duplicate
     * name/id (a typo or rename on HR's side) that this merges away.
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

        Employee::where($column, $stubId)->update([$column => $targetId]);
        $stub->delete();
        $this->flash = ucfirst($type).' merged and duplicate record removed.';
    }

    public function render()
    {
        $lastSync = ApiSyncLog::latest('started_at')->first();

        return view('livewire.admin.sync', [
            'firstSyncCompleted' => Setting::get('hr_first_sync_completed_at') !== null,
            'lastSync' => $lastSync,
            'needsReviewCompanies' => Company::needsReview()->withCount('employees')->get(),
            'needsReviewDepartments' => Department::needsReview()->withCount('employees')->get(),
            'needsReviewDesignations' => Designation::needsReview()->withCount('employees')->get(),
            'companyOptions' => Company::active()->orderBy('name')->get(),
            'departmentOptions' => Department::active()->orderBy('name')->get(),
            'designationOptions' => Designation::active()->orderBy('name')->get(),
            'history' => ApiSyncLog::with('triggeredBy')->latest('started_at')->paginate(10),
        ])->layout('layouts.admin', ['header' => 'HR Synchronization']);
    }
}
