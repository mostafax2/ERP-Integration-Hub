<?php

namespace Mostafax\ErpIntegrationHub\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SyncProfile extends Model
{
    use SoftDeletes, HasFactory;

    protected $table = 'dynamics_sync_profiles';

    protected $fillable = [
        'name', 'slug', 'description', 'connection_id', 'source_model',
        'source_table', 'source_filters', 'source_key', 'destination_entity',
        'destination_key', 'destination_company', 'sync_mode', 'direction',
        'conflict_resolution', 'last_synced_at', 'last_sync_cursor', 'status',
        'delete_on_remove', 'options', 'priority', 'chunk_size', 'retry_limit', 'created_by',
    ];

    protected $casts = [
        'source_filters'  => 'array',
        'options'         => 'array',
        'delete_on_remove' => 'boolean',
        'last_synced_at'  => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $model) {
            if (empty($model->slug)) {
                $model->slug = \Illuminate\Support\Str::slug($model->name);
            }
        });
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(DynamicsConnection::class, 'connection_id');
    }

    public function fieldMappings(): HasMany
    {
        return $this->hasMany(FieldMapping::class, 'sync_profile_id')
            ->orderBy('sort_order')
            ->where('is_ignored', false);
    }

    public function allFieldMappings(): HasMany
    {
        return $this->hasMany(FieldMapping::class, 'sync_profile_id')->orderBy('sort_order');
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(SyncLog::class, 'sync_profile_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(SyncSchedule::class, 'sync_profile_id');
    }

    public function failedSyncs(): HasMany
    {
        return $this->hasMany(FailedSync::class, 'sync_profile_id');
    }

    public function latestLog(): BelongsTo
    {
        return $this->belongsTo(SyncLog::class, 'id', 'sync_profile_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function isRunning(): bool
    {
        return $this->syncLogs()
            ->whereIn('status', ['pending', 'running'])
            ->exists();
    }
}
