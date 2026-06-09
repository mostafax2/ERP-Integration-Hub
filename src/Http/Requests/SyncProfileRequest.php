<?php

namespace Mostafax\ErpIntegrationHub\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                => 'required|string|max:255',
            'description'         => 'nullable|string',
            'connection_id'       => 'required|integer|exists:dynamics_connections,id',
            'source_model'        => 'required|string',
            'source_key'          => 'nullable|string|max:100',
            'source_filters'      => 'nullable|array',
            'destination_entity'  => 'required|string|max:255',
            'destination_key'     => 'nullable|string|max:100',
            'destination_company' => 'nullable|string|max:255',
            'sync_mode'           => 'required|in:manual,scheduled,realtime,incremental,full,event_driven',
            'direction'           => 'required|in:push,pull,bidirectional',
            'conflict_resolution' => 'nullable|in:source_wins,destination_wins,manual',
            'status'              => 'nullable|in:active,inactive,paused',
            'chunk_size'          => 'nullable|integer|min:1|max:5000',
            'retry_limit'         => 'nullable|integer|min:0|max:10',
            'priority'            => 'nullable|integer|min:1|max:100',
            'delete_on_remove'    => 'nullable|boolean',
            'options'             => 'nullable|array',
            'field_mappings'      => 'nullable|array',
            'field_mappings.*.source_field'      => 'required|string',
            'field_mappings.*.destination_field' => 'required|string',
            'field_mappings.*.transformation'    => 'nullable|string',
        ];
    }
}
