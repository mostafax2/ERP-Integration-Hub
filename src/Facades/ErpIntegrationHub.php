<?php

namespace Mostafax\ErpIntegrationHub\Facades;

use Illuminate\Support\Facades\Facade;
use Mostafax\ErpIntegrationHub\Connections\ErpConnectionManager;

/**
 * @method static void extend(string $driver, string $driverClass)
 * @method static array driverOptions()
 * @method static array testConnection(\Mostafax\ErpIntegrationHub\Models\DynamicsConnection $connection)
 * @method static \Mostafax\ErpIntegrationHub\Contracts\ConnectionInterface driver(\Mostafax\ErpIntegrationHub\Models\DynamicsConnection $connection)
 */
class ErpIntegrationHub extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ErpConnectionManager::class;
    }
}
