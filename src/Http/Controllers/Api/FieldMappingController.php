<?php

declare(strict_types=1);

namespace Mostafax\ErpIntegrationHub\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Mostafax\ErpIntegrationHub\FieldMapping\FieldMappingEngine;
use Mostafax\ErpIntegrationHub\FieldMapping\Transformers\TransformerFactory;
use Mostafax\ErpIntegrationHub\Models\FieldMapping;
use Mostafax\ErpIntegrationHub\Models\SyncProfile;

class FieldMappingController extends Controller
{
    public function index(int $profileId): JsonResponse
    {
        $this->authorize('view_sync_profiles');

        $profile  = SyncProfile::findOrFail($profileId);
        $mappings = $profile->allFieldMappings()->orderBy('sort_order')->get();
        return response()->json(['data' => $mappings]);
    }

    public function store(Request $request, int $profileId): JsonResponse
    {
        $this->authorize('manage_sync_profiles');

        $profile      = SyncProfile::findOrFail($profileId);
        $mappingsData = $request->input('mappings', []);
        $profile->allFieldMappings()->delete();

        $created = [];
        foreach ($mappingsData as $idx => $mapping) {
            $created[] = $profile->allFieldMappings()->create(
                array_merge($mapping, ['sort_order' => $idx])
            );
        }

        return response()->json(['data' => $created, 'message' => 'Field mappings saved.']);
    }

    public function update(Request $request, int $profileId, int $mappingId): JsonResponse
    {
        $this->authorize('manage_sync_profiles');

        $allowed = config('erp-integration-hub.allowed_transformers', []);

        $data = $request->validate([
            'source_field'          => 'sometimes|required|string|max:255',
            'destination_field'     => 'sometimes|required|string|max:255',
            'transformation'        => 'nullable|string',
            'transformation_config' => 'nullable|array',
            'default_value'         => 'nullable|string',
            'is_required'           => 'nullable|boolean',
            'is_ignored'            => 'nullable|boolean',
            'is_key_field'          => 'nullable|boolean',
            'custom_transformer'    => ['nullable', 'string', 'in:' . implode(',', $allowed)],
            'sort_order'            => 'nullable|integer',
            'notes'                 => 'nullable|string',
        ]);

        $mapping = FieldMapping::where('sync_profile_id', $profileId)->findOrFail($mappingId);
        $mapping->update($data);
        return response()->json(['data' => $mapping, 'message' => 'Mapping updated.']);
    }

    public function destroy(int $profileId, int $mappingId): JsonResponse
    {
        $this->authorize('manage_sync_profiles');

        FieldMapping::where('sync_profile_id', $profileId)->findOrFail($mappingId)->delete();
        return response()->json(['message' => 'Mapping deleted.']);
    }

    public function preview(Request $request, int $profileId): JsonResponse
    {
        $this->authorize('view_sync_profiles');

        $profile = SyncProfile::with('allFieldMappings')->findOrFail($profileId);
        $engine  = new FieldMappingEngine($profile);
        $result  = $engine->preview($request->input('sample', []));
        return response()->json(['data' => $result]);
    }

    public function transformers(): JsonResponse
    {
        $this->authorize('view_sync_profiles');

        $types = TransformerFactory::all();
        $data  = array_map(fn($t) => ['value' => $t, 'label' => ucwords(str_replace('_', ' ', $t))], $types);
        return response()->json(['data' => $data]);
    }

    public function autoMap(Request $request, int $profileId): JsonResponse
    {
        $this->authorize('manage_sync_profiles');

        SyncProfile::findOrFail($profileId); // verify profile exists

        $source      = $request->input('source_fields', []);
        $destination = $request->input('destination_fields', []);

        $suggestions = [];
        foreach ($source as $sourceField) {
            $best          = $this->findBestMatch($sourceField, $destination);
            $suggestions[] = [
                'source_field'      => $sourceField,
                'destination_field' => $best['field'],
                'confidence'        => $best['score'],
                'transformation'    => 'none',
            ];
        }

        return response()->json(['data' => $suggestions]);
    }

    private function findBestMatch(string $sourceField, array $destinationFields): array
    {
        $best = ['field' => '', 'score' => 0];
        foreach ($destinationFields as $destField) {
            similar_text(strtolower($sourceField), strtolower($destField), $percent);
            if ($percent > $best['score']) {
                $best = ['field' => $destField, 'score' => round($percent)];
            }
        }
        return $best;
    }
}
