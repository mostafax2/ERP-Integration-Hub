<?php

namespace Mostafax\ErpIntegrationHub\DTOs;

use Mostafax\ErpIntegrationHub\Models\DynamicsConnection;

final class ConnectionDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $driver,
        public readonly string $tenantId,
        public readonly string $clientId,
        public readonly string $clientSecret,
        public readonly string $environmentName = '',
        public readonly string $baseUrl = '',
        public readonly string $companyId = '',
        public readonly array $extraConfig = [],
        public readonly bool $isDefault = false,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            driver: $data['driver'] ?? 'business_central',
            tenantId: $data['tenant_id'],
            clientId: $data['client_id'],
            clientSecret: $data['client_secret'],
            environmentName: $data['environment_name'] ?? '',
            baseUrl: $data['base_url'] ?? '',
            companyId: $data['company_id'] ?? '',
            extraConfig: $data['extra_config'] ?? [],
            isDefault: $data['is_default'] ?? false,
        );
    }

    public static function fromModel(DynamicsConnection $model): self
    {
        return new self(
            name: $model->name,
            driver: $model->driver,
            tenantId: $model->tenant_id,
            clientId: $model->client_id,
            clientSecret: $model->client_secret,
            environmentName: $model->environment_name ?? '',
            baseUrl: $model->base_url ?? '',
            companyId: $model->company_id ?? '',
            extraConfig: $model->extra_config ?? [],
            isDefault: $model->is_default,
        );
    }

    public function toArray(): array
    {
        return [
            'name'             => $this->name,
            'driver'           => $this->driver,
            'tenant_id'        => $this->tenantId,
            'client_id'        => $this->clientId,
            'client_secret'    => $this->clientSecret,
            'environment_name' => $this->environmentName,
            'base_url'         => $this->baseUrl,
            'company_id'       => $this->companyId,
            'extra_config'     => $this->extraConfig,
            'is_default'       => $this->isDefault,
        ];
    }
}
