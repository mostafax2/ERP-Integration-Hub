<?php

declare(strict_types=1);

namespace Mostafax\ErpIntegrationHub\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Mostafax\ErpIntegrationHub\Monitoring\MonitoringService;

class MonitoringController extends Controller
{
    public function __construct(private readonly MonitoringService $service) {}

    public function dashboard(): JsonResponse
    {
        $this->authorize('view_monitoring');

        return response()->json(['data' => $this->service->getDashboardStats()]);
    }

    public function health(): JsonResponse
    {
        $this->authorize('view_monitoring');

        $health     = $this->service->getHealthStatus();
        $allHealthy = collect($health)
            ->filter(fn($v) => is_array($v))
            ->every(fn($v) => ($v['status'] ?? '') === 'healthy');

        return response()->json(['data' => $health], $allHealthy ? 200 : 207);
    }

    public function chartData(): JsonResponse
    {
        $this->authorize('view_monitoring');

        return response()->json(['data' => $this->service->getChartData()]);
    }
}
