<?php

namespace Mostafax\ErpIntegrationHub\Connections\Drivers;

class DynamicsFinanceDriver extends AbstractDynamicsDriver
{
    protected function resolveBaseUrl(): string
    {
        $custom = $this->connection->base_url;
        if ($custom) {
            return $custom;
        }
        $template = config('erp-integration-hub.drivers.dynamics_finance.base_url');
        return strtr($template, [
            '{environment}' => $this->connection->environment_name ?: 'prod',
        ]);
    }

    public function fetchEntities(): array
    {
        $response = $this->get('');
        return array_map(fn($e) => [
            'name' => $e['name'],
            'kind' => $e['kind'] ?? 'EntitySet',
            'url'  => $e['url'] ?? $e['name'],
        ], $response['value'] ?? []);
    }

    public function fetchEntityFields(string $entity): array
    {
        $response = $this->get("\$metadata");
        return $response['value'] ?? [];
    }

    public function fetchRecords(string $entity, array $filters = [], int $top = 100, string $skip = null): array
    {
        $params = [
            'cross-company' => 'true',
            '$top'          => $top,
        ];
        if ($filters) {
            $parts = [];
            foreach ($filters as $k => $v) {
                $parts[] = is_string($v) ? "{$k} eq '{$v}'" : "{$k} eq {$v}";
            }
            $params['$filter'] = implode(' and ', $parts);
        }
        if ($skip) {
            $params['$skip'] = $skip;
        }
        return $this->get($entity, $params);
    }

    public function upsertRecord(string $entity, array $data, string $keyField = null): array
    {
        $keyField ??= 'dataAreaId';
        $keyValue = $data[$keyField] ?? null;
        if ($keyValue) {
            try {
                return $this->patch("{$entity}(dataAreaId='{$keyValue}')", $data) ?? [];
            } catch (\Throwable) {}
        }
        return $this->post($entity, $data);
    }

    public function deleteRecord(string $entity, string $id): bool
    {
        return $this->delete("{$entity}({$id})");
    }

    public function getMetadata(): array
    {
        return $this->get('$metadata') ?? [];
    }
}
