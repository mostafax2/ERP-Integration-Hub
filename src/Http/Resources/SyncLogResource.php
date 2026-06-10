<?php

declare(strict_types=1);

namespace Mostafax\ErpIntegrationHub\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SyncLogResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                => $this->id,
            'batch_id'          => $this->batch_id,
            'sync_profile'      => $this->whenLoaded('syncProfile', fn() => [
                'id'   => $this->syncProfile->id,
                'name' => $this->syncProfile->name,
            ]),
            'connection'        => $this->whenLoaded('connection', fn() => [
                'id'   => $this->connection->id,
                'name' => $this->connection->name,
            ]),
            'trigger'           => $this->trigger,
            'status'            => $this->status,
            'started_at'        => $this->started_at?->toIso8601String(),
            'completed_at'      => $this->completed_at?->toIso8601String(),
            'duration'          => $this->duration_formatted,
            'duration_ms'       => $this->duration_ms,
            'total_records'     => $this->total_records,
            'processed_records' => $this->processed_records,
            'success_records'   => $this->success_records,
            'failed_records'    => $this->failed_records,
            'skipped_records'   => $this->skipped_records,
            'message'           => $this->message,
            'errors'            => $this->errors,
            'failed_syncs'      => $this->whenLoaded('failedSyncs'),
            'triggered_by'      => $this->whenLoaded('triggeredBy', fn() => $this->triggeredBy?->name),
            'created_at'        => $this->created_at->toIso8601String(),
        ];
    }
}
