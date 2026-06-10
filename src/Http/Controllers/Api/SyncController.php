<?php

declare(strict_types=1);

namespace Mostafax\ErpIntegrationHub\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Mostafax\ErpIntegrationHub\Actions\RetryFailedSyncAction;
use Mostafax\ErpIntegrationHub\Actions\RunSyncAction;
use Mostafax\ErpIntegrationHub\Models\FailedSync;
use Mostafax\ErpIntegrationHub\Models\SyncLog;
use Mostafax\ErpIntegrationHub\Models\SyncProfile;

class SyncController extends Controller
{
    public function __construct(
        private readonly RunSyncAction $runSyncAction,
        private readonly RetryFailedSyncAction $retryAction,
    ) {}

    public function run(int $profileId, Request $request): JsonResponse
    {
        $this->authorize('run_sync');

        $profile = SyncProfile::findOrFail($profileId);
        $result  = $this->runSyncAction->execute(
            $profile,
            $request->input('trigger', 'manual'),
            $request->boolean('async', true),
            Auth::user()
        );
        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function cancel(int $logId): JsonResponse
    {
        $this->authorize('cancel_sync');

        $log = SyncLog::findOrFail($logId);
        if (! in_array($log->status, ['pending', 'running'], strict: true)) {
            return response()->json(['error' => 'Log is not in a cancellable state.'], 422);
        }
        $log->update(['status' => 'cancelled', 'completed_at' => now()]);
        return response()->json(['message' => 'Sync cancelled.']);
    }

    public function retryOne(int $failedId): JsonResponse
    {
        $this->authorize('retry_sync');

        $failed  = FailedSync::findOrFail($failedId);
        $success = $this->retryAction->retryOne($failed);
        return response()->json($success
            ? ['message' => 'Retry queued.']
            : ['error'   => 'Cannot retry — max attempts reached.'], $success ? 200 : 422);
    }

    public function retryProfile(int $profileId): JsonResponse
    {
        $this->authorize('retry_sync');

        $profile = SyncProfile::findOrFail($profileId);
        $count   = $this->retryAction->retryProfile($profile);
        return response()->json(['message' => "{$count} failed sync(s) queued for retry."]);
    }

    public function retryAll(): JsonResponse
    {
        $this->authorize('retry_sync');

        $count = $this->retryAction->retryAll();
        return response()->json(['message' => "{$count} failed sync(s) queued for retry."]);
    }

    public function status(int $logId): JsonResponse
    {
        $this->authorize('view_logs');

        $log = SyncLog::with('failedSyncs')->findOrFail($logId);
        return response()->json([
            'data' => [
                'id'                => $log->id,
                'status'            => $log->status,
                'progress'          => $log->total_records > 0
                    ? round(($log->processed_records / $log->total_records) * 100, 1)
                    : 0,
                'processed_records' => $log->processed_records,
                'total_records'     => $log->total_records,
                'success_records'   => $log->success_records,
                'failed_records'    => $log->failed_records,
                'duration'          => $log->duration_formatted,
            ],
        ]);
    }
}
