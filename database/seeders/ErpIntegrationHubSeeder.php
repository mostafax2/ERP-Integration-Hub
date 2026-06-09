<?php

namespace Mostafax\ErpIntegrationHub\Database\Seeders;

use Illuminate\Database\Seeder;
use Mostafax\ErpIntegrationHub\Models\DynamicsSetting;
use Mostafax\ErpIntegrationHub\Security\PermissionManager;

class ErpIntegrationHubSeeder extends Seeder
{
    public function __construct(private readonly PermissionManager $pm) {}

    public function run(): void
    {
        // Permissions & Roles
        $this->pm->seedAll();

        // Default Settings
        $settings = [
            ['group' => 'general',       'key' => 'default_chunk_size',   'value' => '500',  'type' => 'int',  'description' => 'Default records per batch'],
            ['group' => 'general',       'key' => 'retry_limit',          'value' => '3',    'type' => 'int',  'description' => 'Max retry attempts for failed records'],
            ['group' => 'notifications', 'key' => 'notify_on_failure',    'value' => '1',    'type' => 'bool', 'description' => 'Send notification on sync failure'],
            ['group' => 'notifications', 'key' => 'notify_on_success',    'value' => '0',    'type' => 'bool', 'description' => 'Send notification on sync success'],
            ['group' => 'monitoring',    'key' => 'retention_days',       'value' => '90',   'type' => 'int',  'description' => 'Log retention in days'],
            ['group' => 'monitoring',    'key' => 'refresh_interval_ms',  'value' => '5000', 'type' => 'int',  'description' => 'Dashboard refresh interval (ms)'],
        ];

        foreach ($settings as $s) {
            DynamicsSetting::firstOrCreate(['key' => $s['key']], $s);
        }
    }
}
