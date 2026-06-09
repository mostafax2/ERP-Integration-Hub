<?php

namespace Mostafax\ErpIntegrationHub\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Mostafax\ErpIntegrationHub\Connections\ErpConnectionManager;
use Mostafax\ErpIntegrationHub\FieldMapping\FieldMappingEngine;
use Mostafax\ErpIntegrationHub\Models\FailedSync;

class RetryFailedSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(private readonly int $failedSyncId) {}

    public function handle(ErpConnectionManager $manager): void
    {
        $failed  = FailedSync::with('syncProfile.connection')->findOrFail($this->failedSyncId);
        $profile = $failed->syncProfile;

        $failed->increment('attempt_count');
        $failed->update(['last_attempted_at' => now(), 'status' => 'retrying']);

        try {
            $driver = $manager->driver($profile->connection);
            $driver->connect();

            $mapper = new FieldMappingEngine($profile);
            $mapped = $mapper->map($failed->record_data ?? []);

            $driver->upsertRecord($profile->destination_entity, $mapped, $profile->destination_key);
            $failed->markResolved('Retry succeeded.');
        } catch (\Throwable $e) {
            if ($failed->attempt_count >= $failed->max_attempts) {
                $failed->abandon();
            } else {
                $failed->update(['status' => 'pending_retry']);
            }
            throw $e;
        }
    }
}
