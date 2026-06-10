<?php

declare(strict_types=1);

namespace Mostafax\ErpIntegrationHub\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Mostafax\ErpIntegrationHub\Actions\CreateConnectionAction;
use Mostafax\ErpIntegrationHub\Actions\TestConnectionAction;
use Mostafax\ErpIntegrationHub\DTOs\ConnectionDTO;
use Mostafax\ErpIntegrationHub\Http\Requests\ConnectionRequest;
use Mostafax\ErpIntegrationHub\Http\Resources\ConnectionResource;
use Mostafax\ErpIntegrationHub\Repositories\ConnectionRepository;
use Mostafax\ErpIntegrationHub\Services\ErpApiService;

class ConnectionController extends Controller
{
    public function __construct(
        private readonly ConnectionRepository $repo,
        private readonly CreateConnectionAction $createAction,
        private readonly TestConnectionAction $testAction,
        private readonly ErpApiService $apiService,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('view_connections');

        return response()->json([
            'data' => ConnectionResource::collection($this->repo->paginate())->response()->getData(true),
        ]);
    }

    public function store(ConnectionRequest $request): JsonResponse
    {
        $this->authorize('manage_connections');

        $connection = $this->createAction->execute(
            ConnectionDTO::fromArray($request->validated()),
            $request->boolean('test_after_create', true)
        );

        return response()->json([
            'data'    => new ConnectionResource($connection),
            'message' => 'Connection created successfully.',
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $this->authorize('view_connections');

        return response()->json(['data' => new ConnectionResource($this->repo->find($id))]);
    }

    public function update(ConnectionRequest $request, int $id): JsonResponse
    {
        $this->authorize('manage_connections');

        $connection = $this->repo->find($id);
        $updated    = $this->repo->update($connection, $request->validated());

        return response()->json([
            'data'    => new ConnectionResource($updated),
            'message' => 'Connection updated successfully.',
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->authorize('manage_connections');

        $this->repo->delete($this->repo->find($id));
        return response()->json(['message' => 'Connection deleted.']);
    }

    public function test(int $id): JsonResponse
    {
        $this->authorize('manage_connections');

        $result = $this->testAction->execute($this->repo->find($id));
        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function entities(int $id): JsonResponse
    {
        $this->authorize('view_connections');

        $entities = $this->apiService->fetchEntities($this->repo->find($id));
        return response()->json(['data' => $entities]);
    }

    public function entityFields(int $id, string $entity): JsonResponse
    {
        $this->authorize('view_connections');

        $fields = $this->apiService->fetchEntityFields($this->repo->find($id), $entity);
        return response()->json(['data' => $fields]);
    }

    public function drivers(): JsonResponse
    {
        $this->authorize('view_connections');

        $drivers = config('erp-integration-hub.drivers', []);
        $options = array_map(fn($k, $v) => ['value' => $k, 'label' => $v['label'] ?? $k, 'icon' => $v['icon'] ?? null],
            array_keys($drivers), $drivers);
        return response()->json(['data' => array_values($options)]);
    }
}
