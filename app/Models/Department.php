<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'hr_ref_id', 'company_id', 'name', 'department_head_id', 'description', 'is_active', 'needs_review',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'needs_review' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function head(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'department_head_id');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Not currently set by any sync path (Department is fully Admin-assigned — see
     * architecture-plan.md §2.4) but kept for symmetry with Company/Designation.
     */
    public function scopeNeedsReview($query)
    {
        return $query->where('needs_review', true);
    }
}
