<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class PhoneFloor extends Model implements Auditable
{
    use AuditableTrait;

    protected $fillable = ['phone_building_id', 'name', 'access_code', 'sort_order'];

    protected static function booted(): void
    {
        static::saved(fn (PhoneFloor $floor) => $floor->building?->markUpdated());
        static::deleted(fn (PhoneFloor $floor) => $floor->building?->markUpdated());
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(PhoneBuilding::class, 'phone_building_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(PhoneEntry::class)->orderBy('sort_order')->orderBy('id');
    }
}
