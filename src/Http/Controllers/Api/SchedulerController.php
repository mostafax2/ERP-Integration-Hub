<?php

namespace Mostafax\ErpIntegrationHub\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Mostafax\ErpIntegrationHub\Models\SyncProfile;
use Mostafax\ErpIntegrationHub\Models\SyncSchedule;

class SchedulerController extends Controller
{
    public function index(): JsonResponse
    {
        $schedules = SyncSchedule::with('syncProfile')->active()->get();
        return response()->json(['data' => $schedules]);
    }

    public function store(Request $request, int $profileId): JsonResponse
    {
        $profile  = SyncProfile::findOrFail($profileId);
        $data     = $request->validate([
            'frequency'       => 'required|string',
            'cron_expression' => 'nullable|string',
            'timezone'        => 'nullable|string',
            'label'           => 'nullable|string',
        ]);

        $cron = $data['frequency'] !== 'custom'
            ? SyncSchedule::getCronForFrequency($data['frequency'])
            : ($data['cron_expression'] ?? '0 * * * *');

        $schedule = $profile->schedules()->create(array_merge($data, [
            'cron_expression' => $cron,
            'next_run_at'     => now()->addMinute(),
            'is_active'       => true,
        ]));

        return response()->json(['data' => $schedule, 'message' => 'Schedule created.'], 201);
    }

    public function update(Request $request, int $profileId, int $scheduleId): JsonResponse
    {
        $schedule = SyncSchedule::where('sync_profile_id', $profileId)->findOrFail($scheduleId);
        $schedule->update($request->all());
        return response()->json(['data' => $schedule, 'message' => 'Schedule updated.']);
    }

    public function destroy(int $profileId, int $scheduleId): JsonResponse
    {
        SyncSchedule::where('sync_profile_id', $profileId)->findOrFail($scheduleId)->delete();
        return response()->json(['message' => 'Schedule deleted.']);
    }

    public function toggle(int $profileId, int $scheduleId): JsonResponse
    {
        $schedule = SyncSchedule::where('sync_profile_id', $profileId)->findOrFail($scheduleId);
        $schedule->update(['is_active' => ! $schedule->is_active]);
        return response()->json(['data' => $schedule, 'message' => 'Schedule toggled.']);
    }

    public function frequencyOptions(): JsonResponse
    {
        return response()->json([
            'data' => [
                ['value' => 'every_minute',     'label' => 'Every Minute',      'cron' => '* * * * *'],
                ['value' => 'every_5_minutes',  'label' => 'Every 5 Minutes',   'cron' => '*/5 * * * *'],
                ['value' => 'every_15_minutes', 'label' => 'Every 15 Minutes',  'cron' => '*/15 * * * *'],
                ['value' => 'every_30_minutes', 'label' => 'Every 30 Minutes',  'cron' => '*/30 * * * *'],
                ['value' => 'hourly',            'label' => 'Hourly',            'cron' => '0 * * * *'],
                ['value' => 'every_6_hours',     'label' => 'Every 6 Hours',     'cron' => '0 */6 * * *'],
                ['value' => 'every_12_hours',    'label' => 'Every 12 Hours',    'cron' => '0 */12 * * *'],
                ['value' => 'daily',             'label' => 'Daily',             'cron' => '0 0 * * *'],
                ['value' => 'weekly',            'label' => 'Weekly',            'cron' => '0 0 * * 0'],
                ['value' => 'monthly',           'label' => 'Monthly',           'cron' => '0 0 1 * *'],
                ['value' => 'custom',            'label' => 'Custom Expression', 'cron' => null],
            ],
        ]);
    }
}
