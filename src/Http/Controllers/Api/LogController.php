<?php

declare(strict_types=1);

namespace Mostafax\ErpIntegrationHub\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Mostafax\ErpIntegrationHub\Http\Resources\SyncLogResource;
use Mostafax\ErpIntegrationHub\Models\FailedSync;
use Mostafax\ErpIntegrationHub\Repositories\SyncLogRepository;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LogController extends Controller
{
    public function __construct(private readonly SyncLogRepository $repo) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('view_logs');

        $logs = $this->repo->paginate(
            perPage: $request->integer('per_page', 20),
            filters: $request->only(['status', 'sync_profile_id', 'from', 'to'])
        );
        return response()->json(SyncLogResource::collection($logs)->response()->getData(true));
    }

    public function show(int $id): JsonResponse
    {
        $this->authorize('view_logs');

        return response()->json(['data' => new SyncLogResource($this->repo->find($id))]);
    }

    public function statistics(): JsonResponse
    {
        $this->authorize('view_logs');

        return response()->json(['data' => $this->repo->statistics()]);
    }

    public function failedJobs(Request $request): JsonResponse
    {
        $this->authorize('view_logs');

        $query = FailedSync::with('syncProfile')
            ->when($request->input('status'), fn($q, $s) => $q->where('status', $s))
            ->when($request->input('profile_id'), fn($q, $id) => $q->where('sync_profile_id', $id))
            ->latest();

        return response()->json($query->paginate($request->integer('per_page', 20)));
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('view_logs');

        $filters = $request->only(['status', 'sync_profile_id', 'from', 'to']);
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename=sync_logs.csv',
        ];

        return response()->stream(function () use ($filters) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'Profile', 'Status', 'Total', 'Success', 'Failed', 'Duration', 'Started At']);

            $this->repo->cursor($filters)->each(function ($log) use ($out) {
                fputcsv($out, [
                    $log->id,
                    $this->sanitizeCsvValue($log->syncProfile?->name),
                    $log->status,
                    $log->total_records,
                    $log->success_records,
                    $log->failed_records,
                    $log->duration_formatted,
                    $log->started_at?->toDateTimeString(),
                ]);
            });

            fclose($out);
        }, 200, $headers);
    }

    private function sanitizeCsvValue(mixed $value): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }
        // Prevent formula injection in Excel/Sheets (= + - @ TAB CR)
        return in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], strict: true)
            ? "'" . $value
            : $value;
    }
}
