<?php

namespace App\Services;

use App\Models\Announcement;
use Illuminate\Support\Collection;

class AnnouncementService
{
    public function forHome(int $limit = 5): Collection
    {
        return Announcement::currentlyVisible()->latest('published_at')->limit($limit)->get();
    }

    public function paginate(int $perPage = 20)
    {
        return Announcement::with('author')->latest('created_at')->paginate($perPage);
    }

    public function create(array $attributes): Announcement
    {
        return Announcement::create($attributes);
    }

    public function update(Announcement $announcement, array $attributes): Announcement
    {
        $announcement->update($attributes);

        return $announcement->refresh();
    }

    public function delete(Announcement $announcement): bool
    {
        return (bool) $announcement->delete();
    }
}
