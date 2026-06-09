<?php

namespace Mostafax\ErpIntegrationHub\Actions;

use Mostafax\ErpIntegrationHub\DTOs\SyncResultDTO;
use Mostafax\ErpIntegrationHub\Events\SyncStarted;
use Mostafax\ErpIntegrationHub\Jobs\RunSyncJob;
use Mostafax\ErpIntegrationHub\Models\SyncLog;
use Mostafax\ErpIntegrationHub\Models\SyncProfile;
use Illuminate\Contracts\Auth\Authenticatable;

class RunSyncAction
{
    public function execute(
        SyncProfile $profile,
        string $trigger = 'manual',
        bool $async = true,
        ?Authenticatable $user = null
    ): array {
        if ($profile->isRunning()) {
            return ['success' => false, 'message' => 'A sync is already running for this profile.'];
        }

        $log = SyncLog::create([
            'sync_profile_id' => $profile->id,
            'connection_id'   => $profile->connection_id,
            'trigger'         => $trigger,
            'status'          => 'pending',
            'triggered_by'    => $user?->getAuthIdentifier(),
            'ip_address'      => request()?->ip(),
        ]);

        event(new SyncStarted($profile, $log, $user));

        if ($async) {
            RunSyncJob::dispatch($profile->id, $log->id)
                ->onQueue(config('erp-integration-hub.queues.default', 'dynamics-sync'));

            return [
                'success'  => true,
                'message'  => 'Sync job queued successfully.',
                'log_id'   => $log->id,
                'async'    => true,
            ];
        }

        // Synchronous execution
        try {
            app(\Mostafax\ErpIntegrationHub\Services\SyncOrchestrator::class)
                ->run($profile, $log);

            return ['success' => true, 'message' => 'Sync completed.', 'log_id' => $log->id, 'async' => false];
        } catch (\Throwable $e) {
            $log->markAsFailed($e->getMessage());
            return ['success' => false, 'message' => $e->getMessage(), 'log_id' => $log->id];
        }
    }
}
