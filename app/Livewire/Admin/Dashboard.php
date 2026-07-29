<?php

namespace App\Livewire\Admin;

use App\Models\ApiSyncLog;
use App\Models\Company;
use App\Models\Department;
use App\Models\Designation;
use App\Repositories\Contracts\CompanyRepositoryInterface;
use App\Repositories\Contracts\DepartmentRepositoryInterface;
use App\Repositories\Contracts\DesignationRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\AuditService;
use App\Services\HrSync\HrSyncService;
use Livewire\Component;

class Dashboard extends Component
{
    public bool $syncing = false;

    public function runSync(HrSyncService $syncService): void
    {
        $this->syncing = true;
        $syncService->sync(\App\Enums\SyncType::Manual, auth()->user());
        $this->syncing = false;
        $this->dispatch('sync-complete');
    }

    public function render(
        EmployeeRepositoryInterface $employees,
        CompanyRepositoryInterface $companies,
        DepartmentRepositoryInterface $departments,
        DesignationRepositoryInterface $designations,
        AuditService $audit,
    ) {
        return view('livewire.admin.dashboard', [
            'employeeCount' => $employees->countVisible(),
            'companyCount' => $companies->count(),
            'unmappedCount' => Company::needsReview()->count() + Department::needsReview()->count() + Designation::needsReview()->count(),
            'lastSync' => ApiSyncLog::latest('started_at')->first(),
            'recentAudits' => $audit->recent(5),
            'quickLinks' => [
                ['route' => 'admin.employees.index', 'icon' => 'fa-users', 'label' => 'Employees', 'count' => $employees->countVisible()],
                ['route' => 'admin.companies.index', 'icon' => 'fa-building', 'label' => 'Companies', 'count' => $companies->count()],
                ['route' => 'admin.departments.index', 'icon' => 'fa-sitemap', 'label' => 'Departments', 'count' => $departments->count()],
                ['route' => 'admin.designations.index', 'icon' => 'fa-award', 'label' => 'Designations', 'count' => Designation::active()->count()],
                ['route' => 'admin.announcements.index', 'icon' => 'fa-bullhorn', 'label' => 'Announcements', 'count' => \App\Models\Announcement::count()],
            ],
        ])->layout('layouts.admin', ['header' => 'Admin Dashboard']);
    }
}
