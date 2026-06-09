<?php

namespace Mostafax\ErpIntegrationHub\Console\Commands;

use Illuminate\Console\Command;
use Mostafax\ErpIntegrationHub\Models\DynamicsSetting;
use Mostafax\ErpIntegrationHub\Security\PermissionManager;

class InstallCommand extends Command
{
    protected $signature   = 'erp-integration-hub:install {--force : Overwrite existing published files}';
    protected $description = 'Install ERP Integration Hub — publish assets, run migrations, seed permissions';

    public function handle(PermissionManager $permissionManager): int
    {
        $this->info('');
        $this->info('  ╔══════════════════════════════════════════╗');
        $this->info('  ║     ERP Integration Hub — Installer      ║');
        $this->info('  ║     by Mostafa Elbayyar                   ║');
        $this->info('  ╚══════════════════════════════════════════╝');
        $this->info('');

        $force = $this->option('force') ? ' --force' : '';

        // 1. Publish configuration
        $this->step('Publishing configuration...');
        $this->callSilent('vendor:publish', [
            '--tag'   => 'erp-integration-hub-config',
            '--force' => $this->option('force'),
        ]);

        // 2. Publish migrations
        $this->step('Publishing migrations...');
        $this->callSilent('vendor:publish', [
            '--tag'   => 'erp-integration-hub-migrations',
            '--force' => $this->option('force'),
        ]);

        // 3. Run migrations
        $this->step('Running migrations...');
        $this->call('migrate', ['--force' => true]);

        // 4. Publish assets
        $this->step('Publishing frontend assets...');
        $this->callSilent('vendor:publish', [
            '--tag'   => 'erp-integration-hub-assets',
            '--force' => $this->option('force'),
        ]);

        // 5. Create permissions
        $this->step('Creating permissions and roles...');
        try {
            $permissionManager->seedAll();
            $this->info('    ✓ Permissions and roles created.');
        } catch (\Throwable $e) {
            $this->warn("    ⚠ Could not create permissions: {$e->getMessage()}");
        }

        // 6. Create default settings
        $this->step('Creating default settings...');
        $this->seedDefaultSettings();

        $this->info('');
        $this->info('  ✅ ERP Integration Hub installed successfully!');
        $this->info('');
        $this->info('  Next steps:');
        $this->info('  1. Add your ERP connection at: /erp-integration-hub/connections');
        $this->info('  2. Create a Sync Profile at: /erp-integration-hub/sync-profiles');
        $this->info('  3. Configure queue worker: php artisan queue:work --queue=dynamics-sync');
        $this->info('');

        return self::SUCCESS;
    }

    private function step(string $message): void
    {
        $this->info("  → {$message}");
    }

    private function seedDefaultSettings(): void
    {
        $defaults = [
            ['group' => 'general',       'key' => 'default_chunk_size',    'value' => '500',   'type' => 'int'],
            ['group' => 'general',       'key' => 'retry_limit',           'value' => '3',     'type' => 'int'],
            ['group' => 'notifications', 'key' => 'notify_on_failure',     'value' => '1',     'type' => 'bool'],
            ['group' => 'notifications', 'key' => 'notify_on_success',     'value' => '0',     'type' => 'bool'],
            ['group' => 'monitoring',    'key' => 'retention_days',        'value' => '90',    'type' => 'int'],
            ['group' => 'monitoring',    'key' => 'refresh_interval_ms',   'value' => '5000',  'type' => 'int'],
        ];

        foreach ($defaults as $setting) {
            DynamicsSetting::firstOrCreate(['key' => $setting['key']], $setting);
        }
        $this->info('    ✓ Default settings created.');
    }
}
