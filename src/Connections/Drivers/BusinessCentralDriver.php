<?php

namespace Mostafax\ErpIntegrationHub\Connections\Drivers;

class BusinessCentralDriver extends AbstractDynamicsDriver
{
    protected function resolveBaseUrl(): string
    {
        $custom = $this->connection->base_url;
        if ($custom) {
            return $custom;
        }

        $template = config('erp-integration-hub.drivers.business_central.base_url');
        return strtr($template, [
            '{tenant_id}'   => $this->connection->tenant_id,
            '{environment}' => $this->connection->environment_name ?: 'production',
        ]);
    }

    public function fetchEntities(): array
    {
        $response = $this->get('');
        return array_map(fn($e) => [
            'name'  => $e['name'],
            'kind'  => $e['kind'] ?? 'EntitySet',
            'url'   => $e['url'] ?? $e['name'],
        ], $response['value'] ?? []);
    }

    public function fetchEntityFields(string $entity): array
    {
        $response = $this->get("\$metadata#EntityType('{$entity}')");
        return $response['value'] ?? [];
    }

    public function fetchRecords(string $entity, array $filters = [], int $top = 100, string $skip = null): array
    {
        $params = ['$top' => $top];
        if ($filters) {
            $params['$filter'] = $this->buildFilter($filters);
        }
        if ($skip) {
            $params['$skiptoken'] = $skip;
        }
        return $this->get($entity, $params);
    }

    public function upsertRecord(string $entity, array $data, string $keyField = null): array
    {
        $keyField ??= 'No';
        $keyValue = $data[$keyField] ?? null;

        if ($keyValue) {
            // Attempt patch first, fall back to post
            try {
                return $this->patch("{$entity}('{$keyValue}')", $data) ?? [];
            } catch (\Throwable) {
                // Record doesn't exist yet
            }
        }

        return $this->post($entity, $data);
    }

    public function deleteRecord(string $entity, string $id): bool
    {
        return $this->delete("{$entity}('{$id}')");
    }

    public function getMetadata(): array
    {
        return $this->get('$metadata') ?? [];
    }

    private function buildFilter(array $filters): string
    {
        $parts = [];
        foreach ($filters as $field => $value) {
            $parts[] = is_string($value)
                ? "{$field} eq '{$value}'"
                : "{$field} eq {$value}";
        }
        return implode(' and ', $parts);
    }
}
