<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Company extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'hr_ref_id', 'name', 'slug', 'address',
        'phone', 'email', 'website', 'is_active', 'needs_review',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'needs_review' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Company $company) {
            $company->slug ??= Str::slug($company->name);
        });
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function designations(): HasMany
    {
        return $this->hasMany(Designation::class);
    }

    public function officeLocations(): HasMany
    {
        return $this->hasMany(OfficeLocation::class);
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
     * Auto-created by HrSyncService when HR sends a company name OCED hasn't seen
     * before — flagged until an Admin opens and saves the record (see Admin\Companies\Index).
     */
    public function scopeNeedsReview($query)
    {
        return $query->where('needs_review', true);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(128)->height(128);
    }
}
