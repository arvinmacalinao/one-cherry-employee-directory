<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class FavoriteService
{
    protected const RECENTLY_VIEWED_LIMIT = 10;

    protected const RECENTLY_VIEWED_TTL_DAYS = 30;

    public function toggle(User $user, Employee $employee): bool
    {
        if ($this->isFavorited($user, $employee)) {
            $user->favoriteEmployees()->detach($employee->id);

            return false;
        }

        $user->favoriteEmployees()->attach($employee->id);

        return true;
    }

    public function isFavorited(User $user, Employee $employee): bool
    {
        return $user->favoriteEmployees()->wherePivot('employee_id', $employee->id)->exists();
    }

    public function listFor(User $user): Collection
    {
        return $user->favoriteEmployees()->with(['company', 'department', 'designation'])->get();
    }

    /**
     * Recently viewed is intentionally not a database table (see architecture-plan.md §3.2) —
     * high write volume, low query value. A capped, per-user cache list is enough.
     */
    public function recordView(User $user, Employee $employee): void
    {
        $key = $this->recentlyViewedKey($user);
        $ids = Cache::get($key, []);

        $ids = array_values(array_filter($ids, fn ($id) => $id !== $employee->id));
        array_unshift($ids, $employee->id);
        $ids = array_slice($ids, 0, self::RECENTLY_VIEWED_LIMIT);

        Cache::put($key, $ids, now()->addDays(self::RECENTLY_VIEWED_TTL_DAYS));
    }

    public function recentlyViewed(User $user, int $limit = 5): Collection
    {
        $ids = array_slice(Cache::get($this->recentlyViewedKey($user), []), 0, $limit);

        if (empty($ids)) {
            return collect();
        }

        $employees = Employee::with(['company', 'department'])->whereIn('id', $ids)->get()->keyBy('id');

        // Preserve most-recently-viewed-first order.
        return collect($ids)->map(fn ($id) => $employees->get($id))->filter()->values();
    }

    protected function recentlyViewedKey(User $user): string
    {
        return "recently_viewed:{$user->id}";
    }
}
