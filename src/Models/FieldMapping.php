<?php

declare(strict_types=1);

namespace Mostafax\ErpIntegrationHub\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FieldMapping extends Model
{
    protected $table = 'dynamics_field_mappings';

    protected $fillable = [
        'sync_profile_id', 'source_field', 'destination_field',
        'transformation', 'transformation_config', 'default_value',
        'is_required', 'is_ignored', 'is_key_field', 'custom_transformer',
        'sort_order', 'notes',
    ];

    protected $casts = [
        'transformation_config' => 'array',
        'is_required'  => 'boolean',
        'is_ignored'   => 'boolean',
        'is_key_field' => 'boolean',
    ];

    public function syncProfile(): BelongsTo
    {
        return $this->belongsTo(SyncProfile::class, 'sync_profile_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_ignored', false);
    }

    public function scopeKeyFields($query)
    {
        return $query->where('is_key_field', true);
    }
}
