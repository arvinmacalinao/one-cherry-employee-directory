<?php

namespace App\Livewire;

use App\Services\PhoneDirectoryService;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Public landing page — the printed "Local Phone Numbers" sheets, one tab per
 * building. Search looks across every building at once so nobody has to guess
 * which tab a local lives on.
 */
class PhoneDirectory extends Component
{
    #[Url(as: 'building')]
    public ?int $buildingId = null;

    #[Url(as: 'q')]
    public string $search = '';

    public function selectBuilding(int $id): void
    {
        $this->buildingId = $id;
        $this->search = '';
    }

    public function render(PhoneDirectoryService $directory)
    {
        $buildings = $directory->buildings();
        $building = $buildings->firstWhere('id', $this->buildingId) ?? $buildings->first();
        $searching = trim($this->search) !== '';

        $floors = $searching
            ? $directory->floors(null, $this->search)
            : ($building ? $directory->floors($building) : collect());

        return view('livewire.phone-directory', [
            'buildings' => $buildings,
            'building' => $building,
            'searching' => $searching,
            'floors' => $floors,
            'resultCount' => $floors->sum(fn ($floor) => $floor->entries->count()),
            'directory' => $directory,
        ])->layout('layouts.public', ['title' => 'Local Phone Numbers']);
    }
}
