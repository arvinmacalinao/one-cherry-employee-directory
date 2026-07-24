<?php

namespace App\Services;

use Illuminate\Support\Collection;
use OwenIt\Auditing\Models\Audit;

class AuditService
{
    /**
     * owen-it/laravel-auditing writes to `audits` automatically whenever an Auditable model
     * (currently Employee) is created/updated/deleted — this service just reads it back.
     */
    public function recent(int $limit = 10): Collection
    {
        return Audit::with('user')->latest()->limit($limit)->get();
    }

    public function paginate(array $filters = [], int $perPage = 25)
    {
        $query = Audit::with('user')->latest();

        if (! empty($filters['event'])) {
            $query->where('event', $filters['event']);
        }

        if (! empty($filters['auditable_type'])) {
            $query->where('auditable_type', $filters['auditable_type']);
        }

        if (! empty($filters['search'])) {
            $term = $filters['search'];
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$term}%"));
        }

        return $query->paginate($perPage)->withQueryString();
    }
}
