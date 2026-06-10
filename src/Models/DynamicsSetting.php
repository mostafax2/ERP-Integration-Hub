<?php

declare(strict_types=1);

namespace Mostafax\ErpIntegrationHub\Models;

use Illuminate\Database\Eloquent\Model;

class DynamicsSetting extends Model
{
    protected $table = 'dynamics_settings';

    protected $fillable = ['group', 'key', 'value', 'type', 'description', 'is_public'];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        if (! $setting) {
            return $default;
        }
        return static::castValue($setting->value, $setting->type);
    }

    public static function set(string $key, mixed $value, string $group = 'general'): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => is_array($value) ? json_encode($value) : (string) $value, 'group' => $group]
        );
    }

    private static function castValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'int'   => (int) $value,
            'bool'  => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json', 'array' => json_decode($value, true),
            default => $value,
        };
    }

    public function scopeGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }
}
