<?php

use Illuminate\Support\Facades\Route;
use Mostafax\ErpIntegrationHub\Http\Controllers\Api;

/*
|--------------------------------------------------------------------------
| ERP Integration Hub — API Routes
|--------------------------------------------------------------------------
*/

Route::group([
    'prefix'     => config('erp-integration-hub.api_prefix', 'api/erp-integration-hub'),
    'middleware' => config('erp-integration-hub.api_middleware', ['api', 'auth:sanctum']),
], function () {

    // ── Connections ────────────────────────────────────────────────────────────
    Route::prefix('connections')->group(function () {
        Route::get('/',                      [Api\ConnectionController::class, 'index']);
        Route::post('/',                     [Api\ConnectionController::class, 'store']);
        Route::get('/drivers',               [Api\ConnectionController::class, 'drivers']);
        Route::get('/{id}',                  [Api\ConnectionController::class, 'show']);
        Route::put('/{id}',                  [Api\ConnectionController::class, 'update']);
        Route::delete('/{id}',               [Api\ConnectionController::class, 'destroy']);
        Route::post('/{id}/test',            [Api\ConnectionController::class, 'test']);
        Route::get('/{id}/entities',         [Api\ConnectionController::class, 'entities']);
        Route::get('/{id}/entities/{entity}/fields', [Api\ConnectionController::class, 'entityFields']);
    });

    // ── Sync Profiles ─────────────────────────────────────────────────────────
    Route::prefix('sync-profiles')->group(function () {
        Route::get('/',                      [Api\SyncProfileController::class, 'index']);
        Route::post('/',                     [Api\SyncProfileController::class, 'store']);
        Route::get('/detect-models',         [Api\SyncProfileController::class, 'detectModels']);
        Route::post('/analyze-model',        [Api\SyncProfileController::class, 'analyzeModel']);
        Route::get('/{id}',                  [Api\SyncProfileController::class, 'show']);
        Route::put('/{id}',                  [Api\SyncProfileController::class, 'update']);
        Route::delete('/{id}',               [Api\SyncProfileController::class, 'destroy']);
    });

    // ── Field Mappings ────────────────────────────────────────────────────────
    Route::prefix('sync-profiles/{profileId}/mappings')->group(function () {
        Route::get('/',                      [Api\FieldMappingController::class, 'index']);
        Route::post('/',                     [Api\FieldMappingController::class, 'store']);
        Route::put('/{mappingId}',           [Api\FieldMappingController::class, 'update']);
        Route::delete('/{mappingId}',        [Api\FieldMappingController::class, 'destroy']);
        Route::post('/preview',              [Api\FieldMappingController::class, 'preview']);
        Route::post('/auto-map',             [Api\FieldMappingController::class, 'autoMap']);
    });
    Route::get('field-mapping/transformers', [Api\FieldMappingController::class, 'transformers']);

    // ── Sync Operations ───────────────────────────────────────────────────────
    Route::prefix('sync')->group(function () {
        Route::post('/run/{profileId}',      [Api\SyncController::class, 'run']);
        Route::post('/cancel/{logId}',       [Api\SyncController::class, 'cancel']);
        Route::get('/status/{logId}',        [Api\SyncController::class, 'status']);
        Route::post('/retry/{failedId}',     [Api\SyncController::class, 'retryOne']);
        Route::post('/retry-profile/{profileId}', [Api\SyncController::class, 'retryProfile']);
        Route::post('/retry-all',            [Api\SyncController::class, 'retryAll']);
    });

    // ── Scheduler ─────────────────────────────────────────────────────────────
    Route::prefix('scheduler')->group(function () {
        Route::get('/',                      [Api\SchedulerController::class, 'index']);
        Route::get('/frequency-options',     [Api\SchedulerController::class, 'frequencyOptions']);
        Route::post('/{profileId}',          [Api\SchedulerController::class, 'store']);
        Route::put('/{profileId}/{scheduleId}', [Api\SchedulerController::class, 'update']);
        Route::delete('/{profileId}/{scheduleId}', [Api\SchedulerController::class, 'destroy']);
        Route::post('/{profileId}/{scheduleId}/toggle', [Api\SchedulerController::class, 'toggle']);
    });

    // ── Monitoring ────────────────────────────────────────────────────────────
    Route::prefix('monitoring')->group(function () {
        Route::get('/dashboard',             [Api\MonitoringController::class, 'dashboard']);
        Route::get('/health',                [Api\MonitoringController::class, 'health']);
        Route::get('/chart-data',            [Api\MonitoringController::class, 'chartData']);
    });

    // ── Logs & Failed Jobs ────────────────────────────────────────────────────
    Route::prefix('logs')->group(function () {
        Route::get('/',                      [Api\LogController::class, 'index']);
        Route::get('/statistics',            [Api\LogController::class, 'statistics']);
        Route::get('/failed-jobs',           [Api\LogController::class, 'failedJobs']);
        Route::get('/export',                [Api\LogController::class, 'export']);
        Route::get('/{id}',                  [Api\LogController::class, 'show']);
    });

    // ── Settings ──────────────────────────────────────────────────────────────
    Route::prefix('settings')->group(function () {
        Route::get('/',                      [Api\SettingsController::class, 'index']);
        Route::post('/',                     [Api\SettingsController::class, 'update']);
        Route::get('/{key}',                 [Api\SettingsController::class, 'get']);
    });
});
