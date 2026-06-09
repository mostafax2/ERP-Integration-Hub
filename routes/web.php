<?php

use Illuminate\Support\Facades\Route;
use Mostafax\ErpIntegrationHub\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| ERP Integration Hub — Web Routes (SPA shell)
|--------------------------------------------------------------------------
*/

Route::group([
    'prefix'     => config('erp-integration-hub.route_prefix', 'erp-integration-hub'),
    'middleware' => config('erp-integration-hub.middleware', ['web', 'auth']),
    'as'         => 'erp-integration-hub.',
], function () {
    Route::get('/{any?}', [DashboardController::class, 'index'])
        ->where('any', '.*')
        ->name('dashboard');
});
