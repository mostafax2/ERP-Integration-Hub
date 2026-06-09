<?php

namespace Mostafax\ErpIntegrationHub\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SyncProfileResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                  => $this->id,
            'name'                => $this->name,
            'slug'                => $this->slug,
            'description'         => $this->description,
            'connection'          => $this->whenLoaded('connection', fn() => new ConnectionResource($this->connection)),
            'source_model'        => $this->source_model,
            'source_table'        => $this->source_table,
            'destination_entity'  => $this->destination_entity,
            'destination_key'     => $this->destination_key,
            'sync_mode'           => $this->sync_mode,
            'direction'           => $this->direction,
            'conflict_resolution' => $this->conflict_resolution,
            'status'              => $this->status,
            'last_synced_at'      => $this->last_synced_at?->toIso8601String(),
            'chunk_size'          => $this->chunk_size,
            'retry_limit'         => $this->retry_limit,
            'is_running'          => $this->isRunning(),
            'field_mappings'      => $this->whenLoaded('allFieldMappings'),
            'schedules'           => $this->whenLoaded('schedules'),
            'sync_logs_count'     => $this->sync_logs_count ?? 0,
            'failed_syncs_count'  => $this->failed_syncs_count ?? 0,
            'created_at'          => $this->created_at->toIso8601String(),
        ];
    }
}
