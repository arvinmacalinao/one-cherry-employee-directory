<?php

namespace App\Models;

use App\Enums\SyncStatus;
use App\Enums\SyncType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiSyncLog extends Model
{
    protected $fillable = [
        'sync_type', 'started_at', 'completed_at', 'status',
        'records_imported', 'records_updated', 'records_transferred', 'records_deactivated',
        'errors', 'triggered_by',
    ];

    protected $casts = [
        'sync_type' => SyncType::class,
        'status' => SyncStatus::class,
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'errors' => 'array',
    ];

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }
}
