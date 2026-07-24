<?php

namespace App\Livewire;

use App\Services\EmployeeDirectoryService;
use App\Services\FavoriteService;
use App\Services\SearchService;
use Livewire\Component;

class Dashboard extends Component
{
    public string $heroSearch = '';

    public bool $showResults = false;

    /** @var array<int> */
    public array $dismissedAnnouncements = [];

    public function dismissAnnouncement(int $id): void
    {
        $this->dismissedAnnouncements[] = $id;
    }

    public function updatedHeroSearch(): void
    {
        $this->showResults = trim($this->heroSearch) !== '';
    }

    public function toggleFavorite(int $employeeId, FavoriteService $favorites): void
    {
        $favorites->toggle(auth()->user(), \App\Models\Employee::findOrFail($employeeId));
    }

    public function render(
        EmployeeDirectoryService $directory,
        FavoriteService $favorites,
        SearchService $search,
    ) {
        return view('livewire.dashboard', [
            'stats' => $directory->dashboardStats(),
            'birthdayCelebrants' => $directory->birthdayCelebrants(),
            'newHires' => $directory->newHires(),
            'recentlyViewed' => $favorites->recentlyViewed(auth()->user()),
            'favorites' => $favorites->listFor(auth()->user())->take(5),
            'searchResults' => $this->showResults ? $search->globalSearch($this->heroSearch) : null,
            'announcements' => collect([
                ['id' => 1, 'icon' => 'fa-bell', 'title' => 'Company Directory now shows Viber shortcuts', 'body' => 'Tap the chat icon on any employee card to message them directly.', 'date' => 'Jul 20, 2026'],
                ['id' => 2, 'icon' => 'fa-calendar-days', 'title' => "Founders' Day — Aug 8, Cherry Tower Atrium", 'body' => 'All Group companies invited. RSVP through your department head.', 'date' => 'Jul 18, 2026'],
            ])->reject(fn ($a) => in_array($a['id'], $this->dismissedAnnouncements)),
        ])->layout('layouts.app', ['header' => 'Dashboard']);
    }
}
