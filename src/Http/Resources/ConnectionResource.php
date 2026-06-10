<?php

declare(strict_types=1);

namespace Mostafax\ErpIntegrationHub\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ConnectionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'slug'             => $this->slug,
            'driver'           => $this->driver,
            'driver_label'     => $this->driver_label,
            'environment_name' => $this->environment_name,
            'tenant_id'        => $this->tenant_id,
            'client_id'        => $this->client_id,
            'company_id'       => $this->company_id,
            'status'           => $this->status,
            'status_message'   => $this->status_message,
            'is_default'       => $this->is_default,
            'last_connected_at' => $this->last_connected_at?->toIso8601String(),
            'last_tested_at'   => $this->last_tested_at?->toIso8601String(),
            'sync_profiles_count' => $this->sync_profiles_count ?? 0,
            'created_at'       => $this->created_at->toIso8601String(),
            'updated_at'       => $this->updated_at->toIso8601String(),
        ];
    }
}
