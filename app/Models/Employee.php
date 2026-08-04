<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Employee extends Model implements Auditable, HasMedia
{
    use AuditableTrait, HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        // HR-owned — overwritten by HrSyncService, never edited directly in the UI.
        'employee_id', 'first_name', 'middle_name', 'last_name', 'username', 'email',
        'company_id', 'department_id', 'designation_id', 'immediate_supervisor_id',
        'employee_status_id', 'is_active', 'date_hired', 'date_regularized', 'date_separated',
        'last_synced_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'date_hired' => 'date',
        'date_regularized' => 'date',
        'date_separated' => 'date',
        'last_synced_at' => 'datetime',
    ];

    /**
     * employee_profiles holds everything HR doesn't send — never touched by sync.
     * Excluding it here keeps the audit trail focused on HR-driven changes.
     */
    protected $auditExclude = [
        'created_at', 'updated_at', 'last_synced_at',
    ];

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(EmployeeStatus::class, 'employee_status_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'immediate_supervisor_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Employee::class, 'immediate_supervisor_id');
    }

    public function profile(): HasOne
    {
        return $this->hasOne(EmployeeProfile::class);
    }

    /**
     * Directory visibility is driven purely by presence in the latest HR sync
     * run — not by interpreting employee_status's name. See architecture-plan.md §2.5.
     */
    public function scopeVisibleInDirectory(Builder $query): Builder
    {
        // Qualified with the table name: sorting by company/department joins in
        // companies/departments, which also have their own is_active column —
        // an unqualified where('is_active', ...) becomes ambiguous SQL once joined.
        return $query->where('employees.is_active', true);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $q) use ($term) {
            $q->whereFullText(['first_name', 'last_name'], $term)
                ->orWhere('email', 'like', "%{$term}%");
        });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photo')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(160)->height(160);
        $this->addMediaConversion('profile')->width(400)->height(400);
    }
}
