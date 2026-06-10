<?php

declare(strict_types=1);

namespace Mostafax\ErpIntegrationHub\Connections\Drivers;

/**
 * Custom ERP connector — allows connecting any REST/OData API
 * by configuring headers, auth, and endpoint patterns via extra_config.
 */
class CustomDriver extends AbstractDynamicsDriver
{
    protected function resolveBaseUrl(): string
    {
        return rtrim($this->connection->base_url ?: 'https://api.example.com', '/') . '/';
    }

    public function connect(): bool
    {
        try {
            $extra  = $this->connection->extra_config ?? [];
            $authType = $extra['auth_type'] ?? 'bearer'; // bearer, basic, api_key, none

            [$this->authScheme, $this->accessToken] = match ($authType) {
                'basic'   => ['Basic',  base64_encode("{$this->connection->client_id}:{$this->connection->decrypted_client_secret}")],
                'api_key' => ['Bearer', $this->connection->decrypted_client_secret],
                'bearer'  => ['Bearer', $this->tokenManager->getToken($this->connection)],
                default   => ['', ''],
            };

            if ($extra['test_endpoint'] ?? null) {
                $this->get($extra['test_endpoint']);
            }
            $this->connection->markAsConnected();
            return true;
        } catch (\Throwable $e) {
            $this->connection->markAsError($e->getMessage());
            return false;
        }
    }

    public function fetchEntities(): array
    {
        $extra    = $this->connection->extra_config ?? [];
        $endpoint = $extra['entities_endpoint'] ?? '';
        if (! $endpoint) {
            return [];
        }
        $response = $this->get($endpoint);
        $path     = $extra['entities_path'] ?? 'value';
        return data_get($response, $path, []);
    }

    public function fetchEntityFields(string $entity): array
    {
        $extra    = $this->connection->extra_config ?? [];
        $endpoint = str_replace('{entity}', $entity, $extra['fields_endpoint'] ?? "{$entity}/fields");
        $response = $this->get($endpoint);
        return $response['fields'] ?? $response['value'] ?? [];
    }

    public function fetchRecords(string $entity, array $filters = [], int $top = 100, string $skip = null): array
    {
        $extra  = $this->connection->extra_config ?? [];
        $params = array_merge(['limit' => $top], $filters);
        if ($skip) {
            $params['skip'] = $skip;
        }
        return $this->get($entity, $params);
    }

    public function upsertRecord(string $entity, array $data, string $keyField = null): array
    {
        $extra    = $this->connection->extra_config ?? [];
        $keyField ??= $extra['key_field'] ?? 'id';
        $keyValue = $data[$keyField] ?? null;
        if ($keyValue) {
            try {
                return $this->patch("{$entity}/{$keyValue}", $data);
            } catch (\Throwable) {}
        }
        return $this->post($entity, $data);
    }

    public function deleteRecord(string $entity, string $id): bool
    {
        return $this->delete("{$entity}/{$id}");
    }

    public function getMetadata(): array
    {
        return [
            'driver'   => 'custom',
            'base_url' => $this->baseUrl,
            'config'   => $this->connection->extra_config ?? [],
        ];
    }

    public function test(): array
    {
        return $this->connect()
            ? ['success' => true, 'message' => 'Custom ERP connection successful']
            : ['success' => false, 'message' => 'Custom ERP connection failed'];
    }
}
