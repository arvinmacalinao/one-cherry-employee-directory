<?php

namespace App\Livewire;

use App\Models\Employee;
use App\Services\FavoriteService;
use App\Services\QrCodeService;
use App\Services\VCardService;
use Livewire\Component;

class EmployeeProfile extends Component
{
    public Employee $employee;

    public function mount(Employee $employee, FavoriteService $favorites): void
    {
        $employee->load(['company', 'department', 'designation', 'profile.officeLocation', 'supervisor', 'reports', 'skills']);
        $this->employee = $employee;

        $favorites->recordView(auth()->user(), $employee);
    }

    public function toggleFavorite(FavoriteService $favorites): void
    {
        $favorites->toggle(auth()->user(), $this->employee);
    }

    public function downloadVCard(VCardService $vCards)
    {
        return response()->streamDownload(
            fn () => print($vCards->generate($this->employee)),
            $vCards->filename($this->employee),
            ['Content-Type' => 'text/vcard'],
        );
    }

    public function render(FavoriteService $favorites, QrCodeService $qrCodes)
    {
        return view('livewire.employee-profile', [
            'isFavorited' => $favorites->isFavorited(auth()->user(), $this->employee),
            'qrCodeSvg' => $qrCodes->svgFor($this->employee),
        ])->layout('layouts.app', ['header' => 'Employee Profile']);
    }
}
