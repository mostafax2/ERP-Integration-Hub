<?php

declare(strict_types=1);

namespace Mostafax\ErpIntegrationHub\Services;

use Illuminate\Support\Facades\Cache;
use Mostafax\ErpIntegrationHub\Connections\ErpConnectionManager;
use Mostafax\ErpIntegrationHub\Models\DynamicsConnection;

class ErpApiService
{
    public function __construct(
        private readonly ErpConnectionManager $manager
    ) {}

    public function fetchEntities(DynamicsConnection $connection): array
    {
        return $this->cached(
            "entities:{$connection->id}",
            fn() => $this->manager->driver($connection)->fetchEntities()
        );
    }

    public function fetchEntityFields(DynamicsConnection $connection, string $entity): array
    {
        return $this->cached(
            "fields:{$connection->id}:{$entity}",
            fn() => $this->manager->driver($connection)->fetchEntityFields($entity)
        );
    }

    public function testConnection(DynamicsConnection $connection): array
    {
        return $this->manager->testConnection($connection);
    }

    public function flushCache(DynamicsConnection $connection): void
    {
        Cache::forget(config('erp-integration-hub.cache.prefix') . ":entities:{$connection->id}");
    }

    private function cached(string $key, callable $callback): mixed
    {
        $prefix = config('erp-integration-hub.cache.prefix', 'dynamics_bridge');
        $ttl    = config('erp-integration-hub.cache.ttl', 300);
        return Cache::driver(config('erp-integration-hub.cache.driver', 'redis'))
            ->remember("{$prefix}:{$key}", $ttl, $callback);
    }
}
