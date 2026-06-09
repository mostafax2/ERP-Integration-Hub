<?php

namespace Mostafax\ErpIntegrationHub\Actions;

use Mostafax\ErpIntegrationHub\Jobs\RetryFailedSyncJob;
use Mostafax\ErpIntegrationHub\Models\FailedSync;
use Mostafax\ErpIntegrationHub\Models\SyncProfile;

class RetryFailedSyncAction
{
    public function retryOne(FailedSync $failedSync): bool
    {
        if (! $failedSync->canRetry()) {
            return false;
        }

        $failedSync->update(['status' => 'retrying', 'last_attempted_at' => now()]);

        RetryFailedSyncJob::dispatch($failedSync->id)
            ->onQueue(config('erp-integration-hub.queues.default', 'dynamics-sync'));

        return true;
    }

    public function retryProfile(SyncProfile $profile): int
    {
        $pending = $profile->failedSyncs()->pendingRetry()->get();
        $count   = 0;
        foreach ($pending as $failed) {
            if ($this->retryOne($failed)) {
                $count++;
            }
        }
        return $count;
    }

    public function retryAll(): int
    {
        $pending = FailedSync::pendingRetry()->get();
        $count   = 0;
        foreach ($pending as $failed) {
            if ($this->retryOne($failed)) {
                $count++;
            }
        }
        return $count;
    }
}
