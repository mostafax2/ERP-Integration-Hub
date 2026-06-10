<?php

declare(strict_types=1);

namespace Mostafax\ErpIntegrationHub\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Mostafax\ErpIntegrationHub\Detection\ModelDetector;
use Mostafax\ErpIntegrationHub\Http\Requests\SyncProfileRequest;
use Mostafax\ErpIntegrationHub\Http\Resources\SyncProfileResource;
use Mostafax\ErpIntegrationHub\Repositories\SyncProfileRepository;

class SyncProfileController extends Controller
{
    public function __construct(
        private readonly SyncProfileRepository $repo,
        private readonly ModelDetector $detector,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('view_sync_profiles');

        $profiles = $this->repo->paginate(
            perPage: $request->integer('per_page', 15),
            filters: $request->only(['status', 'connection_id', 'search'])
        );
        return response()->json(SyncProfileResource::collection($profiles)->response()->getData(true));
    }

    public function store(SyncProfileRequest $request): JsonResponse
    {
        $this->authorize('manage_sync_profiles');

        $profile = $this->repo->create(array_merge(
            $request->validated(),
            ['created_by' => Auth::id()]
        ));

        if ($request->has('field_mappings')) {
            foreach ($request->input('field_mappings', []) as $idx => $mapping) {
                $profile->allFieldMappings()->create(array_merge($mapping, ['sort_order' => $idx]));
            }
        }

        return response()->json([
            'data'    => new SyncProfileResource($this->repo->find($profile->id)),
            'message' => 'Sync profile created successfully.',
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $this->authorize('view_sync_profiles');

        return response()->json(['data' => new SyncProfileResource($this->repo->find($id))]);
    }

    public function update(SyncProfileRequest $request, int $id): JsonResponse
    {
        $this->authorize('manage_sync_profiles');

        $profile = $this->repo->update($this->repo->find($id), $request->validated());

        if ($request->has('field_mappings')) {
            $profile->allFieldMappings()->delete();
            foreach ($request->input('field_mappings', []) as $idx => $mapping) {
                $profile->allFieldMappings()->create(array_merge($mapping, ['sort_order' => $idx]));
            }
        }

        return response()->json(['data' => new SyncProfileResource($profile), 'message' => 'Updated.']);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->authorize('manage_sync_profiles');

        $this->repo->delete($this->repo->find($id));
        return response()->json(['message' => 'Sync profile deleted.']);
    }

    public function detectModels(): JsonResponse
    {
        $this->authorize('manage_sync_profiles');

        return response()->json(['data' => $this->detector->detect()]);
    }

    public function analyzeModel(Request $request): JsonResponse
    {
        $this->authorize('manage_sync_profiles');

        $class = $request->input('model');

        // Only allow models that are in the detected list — prevents arbitrary class reflection
        $allowedModels = array_column($this->detector->detect(), 'class');
        if (! in_array($class, $allowedModels, strict: true)) {
            return response()->json(['error' => "Model [{$class}] not found or not an Eloquent model."], 422);
        }

        $result = $this->detector->detectOne($class);
        if (! $result) {
            return response()->json(['error' => "Model [{$class}] not found or not an Eloquent model."], 422);
        }

        return response()->json(['data' => $result]);
    }
}
