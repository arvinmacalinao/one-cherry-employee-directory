<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Shared, organization-wide master data — not scoped to a company, same
 * reasoning as Department. See architecture-plan.md §2.5.
 */
class Designation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'hr_ref_id', 'name', 'is_active', 'needs_review',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'needs_review' => 'boolean',
    ];

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Auto-created by HrSyncService when HR sends a designation name/id OCED
     * hasn't seen before — flagged until an Admin opens and saves the record.
     */
    public function scopeNeedsReview($query)
    {
        return $query->where('needs_review', true);
    }
}
