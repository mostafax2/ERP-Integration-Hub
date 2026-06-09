<?php

namespace Mostafax\ErpIntegrationHub\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Mostafax\ErpIntegrationHub\DTOs\SyncResultDTO;
use Mostafax\ErpIntegrationHub\Models\SyncLog;
use Mostafax\ErpIntegrationHub\Models\SyncProfile;

class SyncCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly SyncProfile $profile,
        public readonly SyncLog $log,
        public readonly SyncResultDTO $result,
    ) {}
}
