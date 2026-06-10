<?php

declare(strict_types=1);

namespace Mostafax\ErpIntegrationHub\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Mostafax\ErpIntegrationHub\Models\SyncLog;
use Mostafax\ErpIntegrationHub\Models\SyncProfile;
use Mostafax\ErpIntegrationHub\Services\SyncOrchestrator;

class RunSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout;
    public int $tries;

    public function __construct(
        private readonly int $profileId,
        private readonly int $logId,
    ) {
        $this->timeout = config('erp-integration-hub.bpe.timeout', 600);
        $this->tries   = 1; // SyncOrchestrator handles retries per-record
        $this->onQueue(config('erp-integration-hub.queues.default', 'dynamics-sync'));
    }

    public function handle(SyncOrchestrator $orchestrator): void
    {
        $profile = SyncProfile::findOrFail($this->profileId);
        $log     = SyncLog::findOrFail($this->logId);

        $orchestrator->run($profile, $log);
    }

    public function failed(\Throwable $exception): void
    {
        $log = SyncLog::find($this->logId);
        $log?->markAsFailed($exception->getMessage());
    }
}
