<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Designation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'hr_ref_id', 'company_id', 'name', 'hierarchy_level', 'description', 'is_active', 'needs_review',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'hierarchy_level' => 'integer',
        'needs_review' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
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
     * Auto-created by HrSyncService when HR sends a designation name OCED hasn't
     * seen before at that company — flagged until an Admin opens and saves the
     * record (see Admin\Designations\Index).
     */
    public function scopeNeedsReview($query)
    {
        return $query->where('needs_review', true);
    }
}
