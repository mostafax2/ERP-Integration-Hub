<?php

declare(strict_types=1);

namespace Mostafax\ErpIntegrationHub\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Mostafax\ErpIntegrationHub\Models\DynamicsConnection;

class ConnectionEstablished
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly DynamicsConnection $connection) {}
}
