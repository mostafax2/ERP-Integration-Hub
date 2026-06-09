<?php

namespace Mostafax\ErpIntegrationHub\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Mostafax\ErpIntegrationHub\Http\Resources\SyncLogResource;
use Mostafax\ErpIntegrationHub\Models\FailedSync;
use Mostafax\ErpIntegrationHub\Repositories\SyncLogRepository;

class LogController extends Controller
{
    public function __construct(private readonly SyncLogRepository $repo) {}

    public function index(Request $request): JsonResponse
    {
        $logs = $this->repo->paginate(
            perPage: $request->integer('per_page', 20),
            filters: $request->only(['status', 'sync_profile_id', 'from', 'to'])
        );
        return response()->json(SyncLogResource::collection($logs)->response()->getData(true));
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['data' => new SyncLogResource($this->repo->find($id))]);
    }

    public function statistics(): JsonResponse
    {
        return response()->json(['data' => $this->repo->statistics()]);
    }

    public function failedJobs(Request $request): JsonResponse
    {
        $query = FailedSync::with('syncProfile')
            ->when($request->input('status'), fn($q, $s) => $q->where('status', $s))
            ->when($request->input('profile_id'), fn($q, $id) => $q->where('sync_profile_id', $id))
            ->latest();

        return response()->json($query->paginate($request->integer('per_page', 20)));
    }

    public function export(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $logs = $this->repo->paginate(1000, $request->only(['status', 'sync_profile_id', 'from', 'to']));

        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename=sync_logs.csv'];

        return response()->stream(function () use ($logs) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'Profile', 'Status', 'Total', 'Success', 'Failed', 'Duration', 'Started At']);
            foreach ($logs->items() as $log) {
                fputcsv($out, [
                    $log->id,
                    $log->syncProfile?->name,
                    $log->status,
                    $log->total_records,
                    $log->success_records,
                    $log->failed_records,
                    $log->duration_formatted,
                    $log->started_at?->toDateTimeString(),
                ]);
            }
            fclose($out);
        }, 200, $headers);
    }
}
