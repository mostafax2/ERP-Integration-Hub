<?php

declare(strict_types=1);

namespace Mostafax\ErpIntegrationHub\Connections\Drivers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Mostafax\ErpIntegrationHub\Authentication\TokenManager;
use Mostafax\ErpIntegrationHub\Contracts\ConnectionInterface;
use Mostafax\ErpIntegrationHub\Exceptions\ConnectionException;
use Mostafax\ErpIntegrationHub\Models\DynamicsConnection;

abstract class AbstractDynamicsDriver implements ConnectionInterface
{
    protected Client $http;
    protected string $baseUrl;
    protected ?string $accessToken = null;
    protected string $authScheme = 'Bearer';

    public function __construct(
        protected DynamicsConnection $connection,
        protected TokenManager $tokenManager
    ) {
        $this->baseUrl = $this->resolveBaseUrl();
        $this->http = new Client([
            'base_uri' => rtrim($this->baseUrl, '/') . '/',
            'timeout'  => config('erp-integration-hub.sync.timeout_seconds', 300),
            'headers'  => ['Accept' => 'application/json', 'Content-Type' => 'application/json'],
        ]);
    }

    abstract protected function resolveBaseUrl(): string;

    public function connect(): bool
    {
        try {
            $this->accessToken = $this->tokenManager->getToken($this->connection);
            $this->connection->markAsConnected();
            return true;
        } catch (\Throwable $e) {
            $this->connection->markAsError($e->getMessage());
            return false;
        }
    }

    public function disconnect(): void
    {
        $this->tokenManager->invalidate($this->connection);
        $this->accessToken = null;
    }

    public function isConnected(): bool
    {
        return ! empty($this->accessToken);
    }

    public function getStatus(): string
    {
        return $this->connection->status;
    }

    public function test(): array
    {
        try {
            $token = $this->tokenManager->getToken($this->connection);
            $response = $this->get('$metadata', [], $token);
            return ['success' => true, 'message' => 'Connection successful', 'driver' => class_basename($this)];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    protected function get(string $endpoint, array $params = [], ?string $token = null): array
    {
        $token ??= $this->ensureToken();
        try {
            $response = $this->http->get($endpoint, [
                'headers' => ['Authorization' => "{$this->authScheme} {$token}"],
                'query'   => $params,
            ]);
            return json_decode($response->getBody()->getContents(), true) ?? [];
        } catch (RequestException $e) {
            $this->handleRequestException($e);
        }
    }

    protected function post(string $endpoint, array $data, ?string $token = null): array
    {
        $token ??= $this->ensureToken();
        try {
            $response = $this->http->post($endpoint, [
                'headers' => ['Authorization' => "{$this->authScheme} {$token}"],
                'json'    => $data,
            ]);
            return json_decode($response->getBody()->getContents(), true) ?? [];
        } catch (RequestException $e) {
            $this->handleRequestException($e);
        }
    }

    protected function patch(string $endpoint, array $data, ?string $token = null): array
    {
        $token ??= $this->ensureToken();
        try {
            $response = $this->http->patch($endpoint, [
                'headers' => ['Authorization' => "Bearer {$token}", 'If-Match' => '*'],
                'json'    => $data,
            ]);
            return json_decode($response->getBody()->getContents() ?: '{}', true) ?? [];
        } catch (RequestException $e) {
            $this->handleRequestException($e);
        }
    }

    protected function delete(string $endpoint, ?string $token = null): bool
    {
        $token ??= $this->ensureToken();
        try {
            $this->http->delete($endpoint, [
                'headers' => ['Authorization' => "Bearer {$token}", 'If-Match' => '*'],
            ]);
            return true;
        } catch (RequestException $e) {
            $this->handleRequestException($e);
        }
    }

    public function paginate(string $endpoint, array $params = [], int $pageSize = 100): \Generator
    {
        $token = $this->ensureToken();
        $params['$top'] = $pageSize;
        $nextLink = null;

        do {
            $url = $nextLink ?? $endpoint;
            $response = $this->get($url, $nextLink ? [] : $params, $token);
            $records = $response['value'] ?? [];

            yield $records;

            $nextLink = $response['@odata.nextLink'] ?? null;
        } while ($nextLink);
    }

    protected function ensureToken(): string
    {
        if (empty($this->accessToken)) {
            $this->accessToken = $this->tokenManager->getToken($this->connection);
        }
        return $this->accessToken;
    }

    protected function handleRequestException(RequestException $e): never
    {
        $body = $e->hasResponse()
            ? json_decode($e->getResponse()->getBody()->getContents(), true)
            : [];

        $message = $body['error']['message']
            ?? $body['error']['innererror']['message']
            ?? $e->getMessage();

        if ($e->getCode() === 401) {
            $this->tokenManager->refreshToken($this->connection);
            throw new \Mostafax\ErpIntegrationHub\Exceptions\AuthenticationException("Token expired: {$message}");
        }

        throw new ConnectionException("Dynamics API error: {$message}", $e->getCode(), $e);
    }
}
