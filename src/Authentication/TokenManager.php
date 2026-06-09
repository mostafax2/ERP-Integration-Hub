<?php

namespace Mostafax\ErpIntegrationHub\Authentication;

use Illuminate\Support\Facades\Cache;
use Mostafax\ErpIntegrationHub\Models\DynamicsConnection;

class TokenManager
{
    public function __construct(
        private readonly MicrosoftOAuthClient $oauthClient
    ) {}

    public function getToken(DynamicsConnection $connection): string
    {
        return $this->oauthClient->getAccessToken($connection);
    }

    public function refreshToken(DynamicsConnection $connection): string
    {
        return $this->oauthClient->refreshAccessToken($connection);
    }

    public function invalidate(DynamicsConnection $connection): void
    {
        $key = config('erp-integration-hub.cache.prefix', 'dynamics_bridge') . ":token:{$connection->id}";
        Cache::forget($key);
    }

    public function invalidateAll(): void
    {
        // Flush all cached tokens — useful for credential rotations
        Cache::tags([config('erp-integration-hub.cache.prefix', 'dynamics_bridge') . ':tokens'])->flush();
    }
}
