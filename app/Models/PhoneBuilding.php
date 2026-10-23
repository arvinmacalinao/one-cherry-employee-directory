<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class PhoneBuilding extends Model implements Auditable
{
    use AuditableTrait;

    protected $fillable = ['name', 'trunkline', 'mobile', 'last_updated_on', 'sort_order'];

    protected $casts = [
        'last_updated_on' => 'date',
    ];

    public function floors(): HasMany
    {
        return $this->hasMany(PhoneFloor::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Any edit to a floor or line bumps the "Last update" shown on the sheet, the
     * same way someone used to retype it at the bottom of the printout.
     */
    public function markUpdated(): void
    {
        if (! $this->last_updated_on?->isToday()) {
            $this->update(['last_updated_on' => today()]);
        }
    }
}
