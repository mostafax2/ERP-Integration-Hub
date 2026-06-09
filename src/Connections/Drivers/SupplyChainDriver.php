<?php

namespace Mostafax\ErpIntegrationHub\Connections\Drivers;

class SupplyChainDriver extends DynamicsFinanceDriver
{
    // Supply Chain Management shares the same OData API as Finance
    // Only the base URL and available entities differ
    protected function resolveBaseUrl(): string
    {
        $custom = $this->connection->base_url;
        if ($custom) {
            return $custom;
        }
        $template = config('erp-integration-hub.drivers.supply_chain.base_url');
        return strtr($template, [
            '{environment}' => $this->connection->environment_name ?: 'prod',
        ]);
    }
}
