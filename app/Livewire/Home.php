<?php

namespace App\Livewire;

use App\Services\AnnouncementService;
use App\Services\EmployeeDirectoryService;
use App\Services\SearchService;
use Livewire\Component;

/**
 * Search-first landing page for Internal Users — replaces the old analytics-style
 * Dashboard entirely. No stat cards, no charts, no personalization: there is no
 * employee account to personalize against. See architecture-plan.md §6.1.
 */
class Home extends Component
{
    public string $heroSearch = '';

    public bool $showResults = false;

    public function updatedHeroSearch(): void
    {
        $this->showResults = trim($this->heroSearch) !== '';
    }

    public function render(
        EmployeeDirectoryService $directory,
        SearchService $search,
        AnnouncementService $announcements,
    ) {
        return view('livewire.home', [
            'birthdayCelebrants' => $directory->birthdayCelebrants(),
            'newHires' => $directory->newHires(),
            'announcements' => $announcements->forHome(),
            'searchResults' => $this->showResults ? $search->globalSearch($this->heroSearch) : null,
        ])->layout('layouts.app', ['header' => 'Home']);
    }
}
