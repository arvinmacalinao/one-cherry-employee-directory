<?php

namespace App\Models;

use App\Enums\EmployeeSource;
use App\Enums\EmploymentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        // HR-controlled — overwritten by HrSyncService, never edited directly in the UI.
        'employee_id', 'first_name', 'middle_name', 'last_name', 'email',
        'company_id', 'department_id', 'designation_id', 'immediate_supervisor_id',
        'employment_status', 'date_hired', 'date_regularized', 'date_separated', 'job_level',
        // Directory bookkeeping.
        'source', 'last_synced_at',
    ];

    protected $casts = [
        'employment_status' => EmploymentStatus::class,
        'source' => EmployeeSource::class,
        'date_hired' => 'date',
        'date_regularized' => 'date',
        'date_separated' => 'date',
        'last_synced_at' => 'datetime',
        'job_level' => 'integer',
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

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'employee_skill');
    }

    public function favoritedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'employee_favorites')->withPivot('created_at');
    }

    public function scopeVisibleInDirectory(Builder $query): Builder
    {
        return $query->whereIn('employment_status', array_map(
            fn (EmploymentStatus $status) => $status->value,
            EmploymentStatus::visibleInDirectory(),
        ));
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
        $this->addMediaCollection('cover_banner')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(160)->height(160);
        $this->addMediaConversion('profile')->width(400)->height(400);
    }
}
