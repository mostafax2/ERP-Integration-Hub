<?php

namespace Mostafax\ErpIntegrationHub\Console\Commands;

use Illuminate\Console\Command;
use Mostafax\ErpIntegrationHub\Actions\RunSyncAction;
use Mostafax\ErpIntegrationHub\Models\SyncProfile;

class RunSyncCommand extends Command
{
    protected $signature   = 'erp-integration-hub:sync
                                {profile? : Sync profile ID or slug}
                                {--all : Run all active profiles}
                                {--async : Run asynchronously via queue}';
    protected $description = 'Run ERP synchronization for one or all profiles';

    public function handle(RunSyncAction $action): int
    {
        if ($this->option('all')) {
            return $this->runAll($action);
        }

        $identifier = $this->argument('profile');
        if (! $identifier) {
            $this->error('Provide a profile ID/slug or use --all.');
            return self::FAILURE;
        }

        $profile = is_numeric($identifier)
            ? SyncProfile::find($identifier)
            : SyncProfile::where('slug', $identifier)->first();

        if (! $profile) {
            $this->error("Sync profile [{$identifier}] not found.");
            return self::FAILURE;
        }

        return $this->runOne($profile, $action);
    }

    private function runOne(SyncProfile $profile, RunSyncAction $action): int
    {
        $this->info("Running sync: [{$profile->name}]...");
        $result = $action->execute($profile, 'manual', $this->option('async'));

        if ($result['success']) {
            $this->info("✓ " . $result['message'] . " (Log ID: {$result['log_id']})");
            return self::SUCCESS;
        }

        $this->error("✗ " . $result['message']);
        return self::FAILURE;
    }

    private function runAll(RunSyncAction $action): int
    {
        $profiles = SyncProfile::active()->get();
        $this->info("Running {$profiles->count()} active profile(s)...");
        foreach ($profiles as $profile) {
            $this->runOne($profile, $action);
        }
        return self::SUCCESS;
    }
}
