<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Synced verbatim from HR — {hr_ref_id, name} only. OCED never translates or
 * buckets these values; see architecture-plan.md §2.5.
 */
class EmployeeStatus extends Model
{
    protected $fillable = ['hr_ref_id', 'name'];

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
