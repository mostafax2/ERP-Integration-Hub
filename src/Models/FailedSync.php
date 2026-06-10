<?php

declare(strict_types=1);

namespace Mostafax\ErpIntegrationHub\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FailedSync extends Model
{
    protected $table = 'dynamics_failed_syncs';

    protected $fillable = [
        'sync_log_id', 'sync_profile_id', 'record_id', 'record_type',
        'record_data', 'error_message', 'stack_trace', 'error_code',
        'attempt_count', 'max_attempts', 'status', 'last_attempted_at',
        'resolved_at', 'resolution_notes',
    ];

    protected $casts = [
        'record_data'       => 'array',
        'last_attempted_at' => 'datetime',
        'resolved_at'       => 'datetime',
    ];

    public function syncLog(): BelongsTo
    {
        return $this->belongsTo(SyncLog::class, 'sync_log_id');
    }

    public function syncProfile(): BelongsTo
    {
        return $this->belongsTo(SyncProfile::class, 'sync_profile_id');
    }

    public function scopePendingRetry($query)
    {
        return $query->where('status', 'pending_retry')
            ->whereColumn('attempt_count', '<', 'max_attempts');
    }

    public function canRetry(): bool
    {
        return $this->attempt_count < $this->max_attempts
            && in_array($this->status, ['pending_retry', 'retrying']);
    }

    public function markResolved(string $notes = null): void
    {
        $this->update([
            'status'           => 'resolved',
            'resolved_at'      => now(),
            'resolution_notes' => $notes,
        ]);
    }

    public function abandon(): void
    {
        $this->update(['status' => 'abandoned']);
    }
}
