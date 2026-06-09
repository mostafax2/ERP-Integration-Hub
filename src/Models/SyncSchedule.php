<?php

namespace Mostafax\ErpIntegrationHub\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncSchedule extends Model
{
    protected $table = 'dynamics_sync_schedules';

    protected $fillable = [
        'sync_profile_id', 'label', 'frequency', 'cron_expression',
        'timezone', 'is_active', 'next_run_at', 'last_run_at', 'options',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'next_run_at' => 'datetime',
        'last_run_at' => 'datetime',
        'options'     => 'array',
    ];

    protected static array $frequencyMap = [
        'every_minute'      => '* * * * *',
        'every_5_minutes'   => '*/5 * * * *',
        'every_15_minutes'  => '*/15 * * * *',
        'every_30_minutes'  => '*/30 * * * *',
        'hourly'            => '0 * * * *',
        'every_6_hours'     => '0 */6 * * *',
        'every_12_hours'    => '0 */12 * * *',
        'daily'             => '0 0 * * *',
        'weekly'            => '0 0 * * 0',
        'monthly'           => '0 0 1 * *',
    ];

    public function syncProfile(): BelongsTo
    {
        return $this->belongsTo(SyncProfile::class, 'sync_profile_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDue($query)
    {
        return $query->active()->where('next_run_at', '<=', now());
    }

    public static function getCronForFrequency(string $frequency): string
    {
        return self::$frequencyMap[$frequency] ?? '0 * * * *';
    }

    public function updateNextRunAt(): void
    {
        // Uses Cron expression parser to compute next run
        $this->update(['last_run_at' => now()]);
    }
}
