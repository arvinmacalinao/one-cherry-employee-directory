<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class PhoneEntry extends Model implements Auditable
{
    use AuditableTrait;

    protected $fillable = ['phone_floor_id', 'department', 'name', 'local', 'sort_order'];

    protected static function booted(): void
    {
        static::saved(fn (PhoneEntry $entry) => $entry->floor?->building?->markUpdated());
        static::deleted(fn (PhoneEntry $entry) => $entry->floor?->building?->markUpdated());
    }

    public function floor(): BelongsTo
    {
        return $this->belongsTo(PhoneFloor::class, 'phone_floor_id');
    }
}
