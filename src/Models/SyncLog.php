<?php

namespace Mostafax\ErpIntegrationHub\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SyncLog extends Model
{
    protected $table = 'dynamics_sync_logs';

    protected $fillable = [
        'batch_id', 'sync_profile_id', 'connection_id', 'trigger', 'status',
        'started_at', 'completed_at', 'duration_ms', 'total_records',
        'processed_records', 'success_records', 'failed_records', 'skipped_records',
        'summary', 'errors', 'message', 'triggered_by', 'ip_address',
    ];

    protected $casts = [
        'summary'      => 'array',
        'errors'       => 'array',
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function syncProfile(): BelongsTo
    {
        return $this->belongsTo(SyncProfile::class, 'sync_profile_id');
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(DynamicsConnection::class, 'connection_id');
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\\Models\\User'), 'triggered_by');
    }

    public function failedSyncs(): HasMany
    {
        return $this->hasMany(FailedSync::class, 'sync_log_id');
    }

    public function getDurationFormattedAttribute(): string
    {
        if (! $this->duration_ms) {
            return '-';
        }
        $seconds = intdiv($this->duration_ms, 1000);
        if ($seconds < 60) {
            return "{$seconds}s";
        }
        $minutes = intdiv($seconds, 60);
        $remaining = $seconds % 60;
        return "{$minutes}m {$remaining}s";
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function markAsStarted(): void
    {
        $this->update(['status' => 'running', 'started_at' => now()]);
    }

    public function markAsCompleted(array $stats = []): void
    {
        $completedAt = now();
        $durationMs = $this->started_at
            ? $this->started_at->diffInMilliseconds($completedAt)
            : 0;

        $this->update(array_merge([
            'status'       => ($stats['failed_records'] ?? 0) > 0 ? 'partial' : 'completed',
            'completed_at' => $completedAt,
            'duration_ms'  => $durationMs,
        ], $stats));
    }

    public function markAsFailed(string $message, array $errors = []): void
    {
        $this->update([
            'status'       => 'failed',
            'completed_at' => now(),
            'message'      => $message,
            'errors'       => $errors,
        ]);
    }
}
