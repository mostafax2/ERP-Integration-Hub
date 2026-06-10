<?php

declare(strict_types=1);

namespace Mostafax\ErpIntegrationHub\Connections\Drivers;

use Mostafax\ErpIntegrationHub\Exceptions\ConnectionException;

class SapDriver extends AbstractDynamicsDriver
{
    protected function resolveBaseUrl(): string
    {
        return $this->connection->base_url
            ?: 'https://{hostname}:443/sap/opu/odata/sap';
    }

    public function connect(): bool
    {
        try {
            $extra    = $this->connection->extra_config ?? [];
            $authType = $extra['auth_type'] ?? 'basic';

            if ($authType === 'bearer') {
                $this->authScheme   = 'Bearer';
                $this->accessToken  = $this->tokenManager->getToken($this->connection);
            } else {
                // SAP on-premise uses HTTP Basic Auth (username:password)
                $this->authScheme  = 'Basic';
                $this->accessToken = base64_encode(
                    "{$this->connection->client_id}:{$this->connection->decrypted_client_secret}"
                );
            }

            $this->get('');
            $this->connection->markAsConnected();
            return true;
        } catch (\Throwable $e) {
            $this->connection->markAsError($e->getMessage());
            return false;
        }
    }

    public function fetchEntities(): array
    {
        $response = $this->get('');
        return array_map(fn($e) => [
            'name' => $e['name'],
            'kind' => 'EntitySet',
            'url'  => $e['name'],
        ], $response['value'] ?? []);
    }

    public function fetchEntityFields(string $entity): array
    {
        return $this->get("{$entity}/\$metadata")['value'] ?? [];
    }

    public function fetchRecords(string $entity, array $filters = [], int $top = 100, string $skip = null): array
    {
        $params = ['$format' => 'json', '$top' => $top];
        if ($filters) {
            $parts = [];
            foreach ($filters as $k => $v) {
                $parts[] = is_string($v) ? "{$k} eq '{$v}'" : "{$k} eq {$v}";
            }
            $params['$filter'] = implode(' and ', $parts);
        }
        return $this->get("{$entity}", $params);
    }

    public function upsertRecord(string $entity, array $data, string $keyField = null): array
    {
        $keyField ??= 'Guid';
        $keyValue = $data[$keyField] ?? null;
        if ($keyValue) {
            try {
                return $this->patch("{$entity}(guid'{$keyValue}')", $data) ?? [];
            } catch (\Throwable) {}
        }
        return $this->post("{$entity}", $data);
    }

    public function deleteRecord(string $entity, string $id): bool
    {
        return $this->delete("{$entity}('{$id}')");
    }

    public function getMetadata(): array
    {
        return $this->get('$metadata') ?? [];
    }

    public function test(): array
    {
        return $this->connect()
            ? ['success' => true, 'message' => 'SAP connection successful']
            : ['success' => false, 'message' => 'SAP authentication failed'];
    }
}
