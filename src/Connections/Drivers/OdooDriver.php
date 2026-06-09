<?php

namespace Mostafax\ErpIntegrationHub\Connections\Drivers;

use GuzzleHttp\Client;
use Mostafax\ErpIntegrationHub\Authentication\TokenManager;
use Mostafax\ErpIntegrationHub\Exceptions\ConnectionException;
use Mostafax\ErpIntegrationHub\Models\DynamicsConnection;

/**
 * Odoo JSON-RPC connector.
 * Odoo uses JSON-RPC 2.0, not REST/OData — this driver overrides AbstractDynamicsDriver behaviour.
 */
class OdooDriver extends AbstractDynamicsDriver
{
    private int $uid = 0;
    private string $password = '';

    protected function resolveBaseUrl(): string
    {
        return rtrim($this->connection->base_url ?: 'https://your-odoo.odoo.com', '/');
    }

    public function connect(): bool
    {
        try {
            $extra = $this->connection->extra_config ?? [];
            $db    = $extra['database'] ?? 'odoo';
            $login = $extra['login']    ?? 'admin';
            $this->password = $this->connection->decrypted_client_secret;

            $response = $this->rpc('/web/dataset/call_kw', [
                'model'  => 'res.users',
                'method' => 'authenticate',
                'args'   => [$db, $login, $this->password, []],
                'kwargs' => [],
            ]);

            $this->uid = (int) ($response['result'] ?? 0);
            if ($this->uid === 0) {
                throw new ConnectionException('Odoo authentication failed. Check credentials.');
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
        $models = $this->rpcCall('ir.model', 'search_read', [[]], ['fields' => ['model', 'name']]);
        return array_map(fn($m) => ['name' => $m['model'], 'label' => $m['name']], $models['result'] ?? []);
    }

    public function fetchEntityFields(string $entity): array
    {
        $result = $this->rpcCall($entity, 'fields_get', [], ['attributes' => ['string', 'type']]);
        $fields = [];
        foreach ($result['result'] ?? [] as $field => $attrs) {
            $fields[] = ['name' => $field, 'label' => $attrs['string'], 'type' => $attrs['type']];
        }
        return $fields;
    }

    public function fetchRecords(string $entity, array $filters = [], int $top = 100, string $skip = null): array
    {
        $domain = [];
        foreach ($filters as $k => $v) {
            $domain[] = [$k, '=', $v];
        }
        $result = $this->rpcCall($entity, 'search_read', [$domain], ['limit' => $top]);
        return ['value' => $result['result'] ?? []];
    }

    public function upsertRecord(string $entity, array $data, string $keyField = null): array
    {
        if ($keyField && isset($data[$keyField])) {
            $ids = $this->rpcCall($entity, 'search', [[[$keyField, '=', $data[$keyField]]]])['result'] ?? [];
            if (! empty($ids)) {
                $this->rpcCall($entity, 'write', [$ids, $data]);
                return ['id' => $ids[0]];
            }
        }
        $id = $this->rpcCall($entity, 'create', [$data])['result'] ?? null;
        return ['id' => $id];
    }

    public function deleteRecord(string $entity, string $id): bool
    {
        $this->rpcCall($entity, 'unlink', [[(int) $id]]);
        return true;
    }

    public function getMetadata(): array
    {
        return ['driver' => 'odoo', 'uid' => $this->uid];
    }

    public function test(): array
    {
        return $this->connect()
            ? ['success' => true, 'message' => 'Odoo connection successful', 'uid' => $this->uid]
            : ['success' => false, 'message' => 'Odoo authentication failed'];
    }

    private function rpcCall(string $model, string $method, array $args = [], array $kwargs = []): array
    {
        return $this->rpc('/web/dataset/call_kw', compact('model', 'method', 'args', 'kwargs'));
    }

    private function rpc(string $path, array $params): array
    {
        $response = $this->http->post($this->baseUrl . $path, [
            'json' => [
                'jsonrpc' => '2.0',
                'method'  => 'call',
                'params'  => $params,
            ],
        ]);
        return json_decode($response->getBody()->getContents(), true) ?? [];
    }
}
