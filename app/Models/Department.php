<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Shared, organization-wide master data — not scoped to a company. There is
 * one "Sales" department across the whole Group, not one per company; an
 * employee's company comes solely from Employee::company_id. See
 * architecture-plan.md §2.5 and the ug_id-reuse discussion that settled this.
 */
class Department extends Model
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
     * Auto-created by HrSyncService when HR sends a department name/id OCED hasn't
     * seen before — flagged until an Admin opens and saves the record.
     */
    public function scopeNeedsReview($query)
    {
        return $query->where('needs_review', true);
    }
}
