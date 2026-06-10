<?php

declare(strict_types=1);

namespace Mostafax\ErpIntegrationHub\Scheduler;

use Illuminate\Console\Scheduling\Schedule;
use Mostafax\ErpIntegrationHub\Actions\RunSyncAction;
use Mostafax\ErpIntegrationHub\Models\SyncSchedule;

class SyncScheduler
{
    public function __construct(
        private readonly RunSyncAction $runSyncAction
    ) {}

    public function registerSchedules(Schedule $schedule): void
    {
        $activeSchedules = SyncSchedule::with('syncProfile.connection')
            ->active()
            ->get();

        foreach ($activeSchedules as $syncSchedule) {
            $profile = $syncSchedule->syncProfile;
            if (! $profile || $profile->status !== 'active') {
                continue;
            }

            $schedule->call(function () use ($profile) {
                $this->runSyncAction->execute($profile, 'scheduled', true);
            })
            ->cron($syncSchedule->cron_expression)
            ->timezone($syncSchedule->timezone)
            ->name("erp-bridge:sync:{$profile->slug}")
            ->withoutOverlapping(10)
            ->after(fn() => $syncSchedule->update(['last_run_at' => now()]));
        }
    }

    public function runDue(): void
    {
        $due = SyncSchedule::with('syncProfile.connection')->due()->get();
        foreach ($due as $schedule) {
            $profile = $schedule->syncProfile;
            if ($profile && $profile->status === 'active') {
                $this->runSyncAction->execute($profile, 'scheduled', true);
                $schedule->update(['last_run_at' => now()]);
            }
        }
    }
}
