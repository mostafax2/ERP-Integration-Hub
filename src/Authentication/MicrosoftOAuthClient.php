<?php

namespace Mostafax\ErpIntegrationHub\Authentication;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;
use Mostafax\ErpIntegrationHub\Models\DynamicsConnection;

class MicrosoftOAuthClient
{
    private Client $http;

    public function __construct()
    {
        $this->http = new Client(['timeout' => 30, 'connect_timeout' => 10]);
    }

    public function getAccessToken(DynamicsConnection $connection): string
    {
        $cacheKey = $this->tokenCacheKey($connection->id);

        return Cache::driver(config('erp-integration-hub.cache.driver', 'redis'))
            ->remember($cacheKey, config('erp-integration-hub.cache.token_ttl', 3540), function () use ($connection) {
                return $this->fetchToken($connection);
            });
    }

    public function refreshAccessToken(DynamicsConnection $connection): string
    {
        $cacheKey = $this->tokenCacheKey($connection->id);
        Cache::forget($cacheKey);
        return $this->getAccessToken($connection);
    }

    private function fetchToken(DynamicsConnection $connection): string
    {
        $authority = config('erp-integration-hub.microsoft.authority_url');
        $tenantId  = $connection->tenant_id;
        $tokenUrl  = "{$authority}/{$tenantId}" . config('erp-integration-hub.microsoft.token_endpoint');

        $scopes = config("erp-integration-hub.drivers.{$connection->driver}.scopes",
            config('erp-integration-hub.microsoft.scopes'));

        try {
            $response = $this->http->post($tokenUrl, [
                'form_params' => [
                    'grant_type'    => 'client_credentials',
                    'client_id'     => $connection->client_id,
                    'client_secret' => $connection->decrypted_client_secret,
                    'scope'         => implode(' ', (array) $scopes),
                ],
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            if (empty($body['access_token'])) {
                throw new \RuntimeException('No access_token in Microsoft OAuth response.');
            }

            return $body['access_token'];
        } catch (RequestException $e) {
            $errorBody = $e->hasResponse()
                ? json_decode($e->getResponse()->getBody()->getContents(), true)
                : [];

            throw new \Mostafax\ErpIntegrationHub\Exceptions\AuthenticationException(
                $errorBody['error_description'] ?? $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    public function testConnection(DynamicsConnection $connection): array
    {
        try {
            $token = $this->fetchToken($connection);
            return [
                'success' => true,
                'message' => 'Authentication successful',
                'token_length' => strlen($token),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'error'   => class_basename($e),
            ];
        }
    }

    private function tokenCacheKey(int $connectionId): string
    {
        return config('erp-integration-hub.cache.prefix', 'dynamics_bridge') . ":token:{$connectionId}";
    }
}
