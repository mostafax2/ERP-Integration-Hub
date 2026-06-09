<?php

namespace Mostafax\ErpIntegrationHub\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class DashboardController extends Controller
{
    public function index(): Response
    {
        return response()->view('erp-integration-hub::app', [
            'config' => [
                'api_prefix'   => config('erp-integration-hub.api_prefix'),
                'brand_name'   => config('erp-integration-hub.ui.brand_name'),
                'default_locale' => config('erp-integration-hub.ui.default_locale'),
                'supported_locales' => config('erp-integration-hub.ui.supported_locales'),
                'rtl_locales'  => config('erp-integration-hub.ui.rtl_locales'),
                'default_theme' => config('erp-integration-hub.ui.default_theme'),
                'refresh_interval' => config('erp-integration-hub.monitoring.refresh_interval'),
            ],
        ]);
    }
}
