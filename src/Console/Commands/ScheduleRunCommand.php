<?php

declare(strict_types=1);

namespace Mostafax\ErpIntegrationHub\Console\Commands;

use Illuminate\Console\Command;
use Mostafax\ErpIntegrationHub\Scheduler\SyncScheduler;

class ScheduleRunCommand extends Command
{
    protected $signature   = 'erp-integration-hub:schedule-run';
    protected $description = 'Run all due ERP sync schedules (called by Laravel scheduler)';

    public function handle(SyncScheduler $scheduler): int
    {
        $this->info('Checking for due ERP sync schedules...');
        $scheduler->runDue();
        $this->info('Done.');
        return self::SUCCESS;
    }
}
