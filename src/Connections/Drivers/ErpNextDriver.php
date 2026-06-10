<?php

declare(strict_types=1);

namespace Mostafax\ErpIntegrationHub\Connections\Drivers;

/**
 * ERPNext REST API connector.
 * ERPNext provides a standard REST API with token-based authentication.
 */
class ErpNextDriver extends AbstractDynamicsDriver
{
    protected function resolveBaseUrl(): string
    {
        return rtrim($this->connection->base_url ?: 'https://your-erpnext.com', '/') . '/api/resource';
    }

    public function connect(): bool
    {
        try {
            $extra  = $this->connection->extra_config ?? [];
            $apiKey    = $extra['api_key']    ?? $this->connection->client_id;
            $apiSecret = $this->connection->decrypted_client_secret;

            // ERPNext uses "token key:secret" scheme, not Bearer
            $this->authScheme  = 'token';
            $this->accessToken = base64_encode("{$apiKey}:{$apiSecret}");
            $this->get('User/me');
            $this->connection->markAsConnected();
            return true;
        } catch (\Throwable $e) {
            $this->connection->markAsError($e->getMessage());
            return false;
        }
    }

    public function fetchEntities(): array
    {
        $response = $this->get('DocType', ['fields' => '["name","module"]', 'limit' => 500]);
        return array_map(fn($e) => ['name' => $e['name'], 'kind' => 'EntitySet'], $response['data'] ?? []);
    }

    public function fetchEntityFields(string $entity): array
    {
        $response = $this->get("DocType/{$entity}");
        $doc = $response['data'] ?? [];
        return array_map(fn($f) => [
            'fieldname' => $f['fieldname'],
            'label'     => $f['label'],
            'fieldtype' => $f['fieldtype'],
        ], $doc['fields'] ?? []);
    }

    public function fetchRecords(string $entity, array $filters = [], int $top = 100, string $skip = null): array
    {
        $params = ['limit' => $top];
        if ($filters) {
            $params['filters'] = json_encode(array_map(fn($k, $v) => [$entity, $k, '=', $v], array_keys($filters), $filters));
        }
        if ($skip) {
            $params['limit_start'] = $skip;
        }
        return $this->get($entity, $params);
    }

    public function upsertRecord(string $entity, array $data, string $keyField = null): array
    {
        $keyField ??= 'name';
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
        return ['driver' => 'erpnext', 'base_url' => $this->baseUrl];
    }

    public function test(): array
    {
        return $this->connect()
            ? ['success' => true, 'message' => 'ERPNext connection successful']
            : ['success' => false, 'message' => 'ERPNext authentication failed'];
    }
}
