<?php

declare(strict_types=1);

namespace Mostafax\ErpIntegrationHub\Actions;

use Mostafax\ErpIntegrationHub\Connections\ErpConnectionManager;
use Mostafax\ErpIntegrationHub\Models\DynamicsConnection;

class TestConnectionAction
{
    public function __construct(
        private readonly ErpConnectionManager $manager
    ) {}

    public function execute(DynamicsConnection $connection): array
    {
        $result = $this->manager->testConnection($connection);

        $connection->update(['last_tested_at' => now()]);

        if ($result['success']) {
            $connection->markAsConnected();
        } else {
            $connection->markAsError($result['message']);
        }

        return $result;
    }
}
