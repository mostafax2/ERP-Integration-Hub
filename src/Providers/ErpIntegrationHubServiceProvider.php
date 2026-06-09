<?php

namespace Mostafax\ErpIntegrationHub\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Mostafax\ErpIntegrationHub\Actions\CreateConnectionAction;
use Mostafax\ErpIntegrationHub\Actions\RetryFailedSyncAction;
use Mostafax\ErpIntegrationHub\Actions\RunSyncAction;
use Mostafax\ErpIntegrationHub\Actions\TestConnectionAction;
use Mostafax\ErpIntegrationHub\Authentication\MicrosoftOAuthClient;
use Mostafax\ErpIntegrationHub\Authentication\TokenManager;
use Mostafax\ErpIntegrationHub\Connections\ErpConnectionManager;
use Mostafax\ErpIntegrationHub\Console\Commands\InstallCommand;
use Mostafax\ErpIntegrationHub\Console\Commands\RunSyncCommand;
use Mostafax\ErpIntegrationHub\Console\Commands\ScheduleRunCommand;
use Mostafax\ErpIntegrationHub\Detection\ModelDetector;
use Mostafax\ErpIntegrationHub\Monitoring\MonitoringService;
use Mostafax\ErpIntegrationHub\Repositories\ConnectionRepository;
use Mostafax\ErpIntegrationHub\Repositories\SyncLogRepository;
use Mostafax\ErpIntegrationHub\Repositories\SyncProfileRepository;
use Mostafax\ErpIntegrationHub\Scheduler\SyncScheduler;
use Mostafax\ErpIntegrationHub\Security\PermissionManager;
use Mostafax\ErpIntegrationHub\Services\ErpApiService;
use Mostafax\ErpIntegrationHub\Services\SyncOrchestrator;

class ErpIntegrationHubServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/erp-integration-hub.php', 'erp-integration-hub');

        // Core singletons
        $this->app->singleton(MicrosoftOAuthClient::class);
        $this->app->singleton(TokenManager::class);
        $this->app->singleton(ErpConnectionManager::class);
        $this->app->singleton(MonitoringService::class);
        $this->app->singleton(ModelDetector::class);
        $this->app->singleton(PermissionManager::class);

        // Services
        $this->app->bind(SyncOrchestrator::class);
        $this->app->bind(ErpApiService::class);
        $this->app->bind(SyncScheduler::class);

        // Repositories
        $this->app->bind(ConnectionRepository::class);
        $this->app->bind(SyncProfileRepository::class);
        $this->app->bind(SyncLogRepository::class);

        // Actions
        $this->app->bind(CreateConnectionAction::class);
        $this->app->bind(TestConnectionAction::class);
        $this->app->bind(RunSyncAction::class);
        $this->app->bind(RetryFailedSyncAction::class);
    }

    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__ . '/../../config/erp-integration-hub.php' => config_path('erp-integration-hub.php'),
        ], 'erp-integration-hub-config');

        // Publish migrations
        $this->publishes([
            __DIR__ . '/../../database/migrations' => database_path('migrations'),
        ], 'erp-integration-hub-migrations');

        // Publish assets
        $this->publishes([
            __DIR__ . '/../../public' => public_path('vendor/erp-integration-hub'),
        ], 'erp-integration-hub-assets');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'erp-integration-hub');

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');

        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                RunSyncCommand::class,
                ScheduleRunCommand::class,
            ]);
        }

        // Register scheduler
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            // Run due sync schedules every minute
            $schedule->command('erp-integration-hub:schedule-run')
                ->everyMinute()
                ->withoutOverlapping();
        });
    }
}
