<?php

namespace App\Livewire\Admin\AuditLogs;

use App\Services\AuditService;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $event = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingEvent(): void
    {
        $this->resetPage();
    }

    public function render(AuditService $audit)
    {
        return view('livewire.admin.audit-logs.index', [
            'logs' => $audit->paginate(['search' => $this->search, 'event' => $this->event]),
        ])->layout('layouts.admin', ['header' => 'Audit Logs']);
    }
}
