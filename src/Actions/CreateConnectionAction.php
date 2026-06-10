<?php

declare(strict_types=1);

namespace Mostafax\ErpIntegrationHub\Actions;

use Mostafax\ErpIntegrationHub\Connections\ErpConnectionManager;
use Mostafax\ErpIntegrationHub\DTOs\ConnectionDTO;
use Mostafax\ErpIntegrationHub\Models\DynamicsConnection;
use Illuminate\Support\Facades\DB;

class CreateConnectionAction
{
    public function __construct(
        private readonly ErpConnectionManager $manager
    ) {}

    public function execute(ConnectionDTO $dto, bool $testAfterCreate = true): DynamicsConnection
    {
        return DB::transaction(function () use ($dto, $testAfterCreate) {
            if ($dto->isDefault) {
                DynamicsConnection::where('is_default', true)->update(['is_default' => false]);
            }

            $connection = DynamicsConnection::create($dto->toArray());

            if ($testAfterCreate) {
                $result = $this->manager->testConnection($connection);
                if ($result['success']) {
                    $connection->markAsConnected();
                } else {
                    $connection->markAsError($result['message']);
                }
            }

            return $connection->fresh();
        });
    }
}
