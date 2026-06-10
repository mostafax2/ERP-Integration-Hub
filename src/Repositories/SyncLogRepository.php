<?php

declare(strict_types=1);

namespace Mostafax\ErpIntegrationHub\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\LazyCollection;
use Mostafax\ErpIntegrationHub\Models\SyncLog;

class SyncLogRepository
{
    public function paginate(int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        $query = SyncLog::with(['syncProfile', 'connection', 'triggeredBy'])
            ->latest();

        if ($status = $filters['status'] ?? null) {
            $query->where('status', $status);
        }
        if ($profileId = $filters['sync_profile_id'] ?? null) {
            $query->where('sync_profile_id', $profileId);
        }
        if ($from = $filters['from'] ?? null) {
            $query->where('created_at', '>=', $from);
        }
        if ($to = $filters['to'] ?? null) {
            $query->where('created_at', '<=', $to);
        }

        return $query->paginate($perPage);
    }

    public function cursor(array $filters = []): LazyCollection
    {
        $query = SyncLog::with('syncProfile')->latest();

        if ($status = $filters['status'] ?? null) {
            $query->where('status', $status);
        }
        if ($profileId = $filters['sync_profile_id'] ?? null) {
            $query->where('sync_profile_id', $profileId);
        }
        if ($from = $filters['from'] ?? null) {
            $query->where('created_at', '>=', $from);
        }
        if ($to = $filters['to'] ?? null) {
            $query->where('created_at', '<=', $to);
        }

        return $query->cursor();
    }

    public function find(int $id): SyncLog
    {
        return SyncLog::with(['syncProfile', 'connection', 'failedSyncs'])->findOrFail($id);
    }

    public function statistics(int $days = 30): array
    {
        $base = SyncLog::recent($days);
        return [
            'total'     => (clone $base)->count(),
            'completed' => (clone $base)->completed()->count(),
            'failed'    => (clone $base)->failed()->count(),
            'pending'   => (clone $base)->whereIn('status', ['pending', 'running'])->count(),
            'total_records_processed' => (clone $base)->sum('success_records'),
            'avg_duration_ms' => (clone $base)->completed()->avg('duration_ms'),
            'success_rate' => $this->successRate($days),
        ];
    }

    private function successRate(int $days): float
    {
        $total     = SyncLog::recent($days)->whereIn('status', ['completed', 'failed', 'partial'])->count();
        $completed = SyncLog::recent($days)->completed()->count();
        return $total > 0 ? round(($completed / $total) * 100, 1) : 0.0;
    }

    public function purgeOld(int $days): int
    {
        return SyncLog::where('created_at', '<', now()->subDays($days))->delete();
    }
}
