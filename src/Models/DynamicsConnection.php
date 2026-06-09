<?php

namespace Mostafax\ErpIntegrationHub\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Crypt;

class DynamicsConnection extends Model
{
    use SoftDeletes, HasFactory;

    protected $table = 'dynamics_connections';

    protected $fillable = [
        'name', 'slug', 'driver', 'environment_name', 'tenant_id',
        'client_id', 'client_secret', 'base_url', 'company_id',
        'extra_config', 'status', 'status_message', 'last_connected_at',
        'last_tested_at', 'is_default', 'created_by',
    ];

    protected $hidden = ['client_secret'];

    protected $casts = [
        'extra_config'      => 'array',
        'is_default'        => 'boolean',
        'last_connected_at' => 'datetime',
        'last_tested_at'    => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->slug)) {
                $model->slug = \Illuminate\Support\Str::slug($model->name);
            }
            if (config('erp-integration-hub.security.encrypt_credentials')) {
                $model->client_secret = Crypt::encryptString($model->client_secret);
            }
        });
    }

    public function getDecryptedClientSecretAttribute(): string
    {
        if (config('erp-integration-hub.security.encrypt_credentials')) {
            return Crypt::decryptString($this->client_secret);
        }
        return $this->client_secret;
    }

    public function syncProfiles(): HasMany
    {
        return $this->hasMany(SyncProfile::class, 'connection_id');
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(SyncLog::class, 'connection_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function markAsConnected(): void
    {
        $this->update(['status' => 'active', 'last_connected_at' => now(), 'status_message' => null]);
    }

    public function markAsError(string $message): void
    {
        $this->update(['status' => 'error', 'status_message' => $message]);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function getDriverLabelAttribute(): string
    {
        return config("erp-integration-hub.drivers.{$this->driver}.label", $this->driver);
    }
}
