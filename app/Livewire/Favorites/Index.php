<?php

namespace App\Livewire\Favorites;

use App\Services\FavoriteService;
use Livewire\Component;

class Index extends Component
{
    public function toggle(int $employeeId, FavoriteService $favorites): void
    {
        $favorites->toggle(auth()->user(), \App\Models\Employee::findOrFail($employeeId));
    }

    public function render(FavoriteService $favorites)
    {
        return view('livewire.favorites.index', [
            'favorites' => $favorites->listFor(auth()->user()),
        ])->layout('layouts.app', ['header' => 'Favorites']);
    }
}
