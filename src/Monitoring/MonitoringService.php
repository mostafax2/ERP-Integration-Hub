<?php

namespace Mostafax\ErpIntegrationHub\Monitoring;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Mostafax\ErpIntegrationHub\Models\DynamicsConnection;
use Mostafax\ErpIntegrationHub\Models\FailedSync;
use Mostafax\ErpIntegrationHub\Models\SyncLog;
use Mostafax\ErpIntegrationHub\Models\SyncProfile;

class MonitoringService
{
    private string $prefix;

    public function __construct()
    {
        $this->prefix = config('erp-integration-hub.cache.prefix', 'dynamics_bridge');
    }

    public function getDashboardStats(): array
    {
        return Cache::remember("{$this->prefix}:dashboard_stats", 30, function () {
            $logs7d  = SyncLog::recent(7);
            $logs30d = SyncLog::recent(30);

            return [
                'connections' => [
                    'total'    => DynamicsConnection::count(),
                    'active'   => DynamicsConnection::active()->count(),
                    'error'    => DynamicsConnection::where('status', 'error')->count(),
                ],
                'sync_profiles' => [
                    'total'   => SyncProfile::count(),
                    'active'  => SyncProfile::active()->count(),
                    'running' => $this->getActiveJobCount(),
                ],
                'jobs_today' => [
                    'total'     => (clone $logs7d)->whereDate('created_at', today())->count(),
                    'completed' => (clone $logs7d)->whereDate('created_at', today())->completed()->count(),
                    'failed'    => (clone $logs7d)->whereDate('created_at', today())->failed()->count(),
                    'pending'   => SyncLog::whereIn('status', ['pending', 'running'])->count(),
                ],
                'records_30d' => [
                    'total_processed' => (clone $logs30d)->sum('processed_records'),
                    'total_success'   => (clone $logs30d)->sum('success_records'),
                    'total_failed'    => (clone $logs30d)->sum('failed_records'),
                ],
                'success_rate' => $this->getSuccessRate(30),
                'avg_duration_ms' => (clone $logs7d)->completed()->avg('duration_ms'),
                'failed_jobs'  => FailedSync::where('status', 'pending_retry')->count(),
                'queue_size'   => $this->getQueueSize(),
                'chart_data'   => $this->getChartData(),
            ];
        });
    }

    public function getChartData(int $days = 14): array
    {
        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $logs = SyncLog::whereDate('created_at', $date);
            $data[] = [
                'date'      => $date,
                'completed' => (clone $logs)->completed()->count(),
                'failed'    => (clone $logs)->failed()->count(),
                'records'   => (clone $logs)->sum('success_records'),
            ];
        }
        return $data;
    }

    public function getHealthStatus(): array
    {
        return [
            'queue'       => $this->checkQueueHealth(),
            'connections' => $this->checkConnectionsHealth(),
            'failed_jobs' => $this->checkFailedJobsHealth(),
            'timestamp'   => now()->toIso8601String(),
        ];
    }

    private function getActiveJobCount(): int
    {
        return SyncLog::whereIn('status', ['pending', 'running'])->count();
    }

    private function getQueueSize(): int
    {
        try {
            return Queue::size(config('erp-integration-hub.queues.default', 'dynamics-sync'));
        } catch (\Throwable) {
            return -1;
        }
    }

    private function getSuccessRate(int $days): float
    {
        $total     = SyncLog::recent($days)->whereIn('status', ['completed', 'failed', 'partial'])->count();
        $completed = SyncLog::recent($days)->completed()->count();
        return $total > 0 ? round(($completed / $total) * 100, 1) : 0.0;
    }

    private function checkQueueHealth(): array
    {
        $size      = $this->getQueueSize();
        $threshold = config('erp-integration-hub.monitoring.alert_threshold.queue_size', 1000);
        return [
            'status'  => $size < $threshold ? 'healthy' : 'warning',
            'size'    => $size,
            'message' => $size < $threshold ? 'Queue is healthy' : "Queue size ({$size}) exceeds threshold",
        ];
    }

    private function checkConnectionsHealth(): array
    {
        $errors = DynamicsConnection::where('status', 'error')->count();
        return [
            'status'  => $errors === 0 ? 'healthy' : 'warning',
            'errors'  => $errors,
            'message' => $errors === 0 ? 'All connections healthy' : "{$errors} connection(s) in error state",
        ];
    }

    private function checkFailedJobsHealth(): array
    {
        $failed    = FailedSync::where('status', 'pending_retry')->count();
        $threshold = config('erp-integration-hub.monitoring.alert_threshold.failed_jobs', 10);
        return [
            'status'  => $failed <= $threshold ? 'healthy' : 'warning',
            'count'   => $failed,
            'message' => $failed <= $threshold ? 'Failed jobs within threshold' : "{$failed} pending retries",
        ];
    }
}
