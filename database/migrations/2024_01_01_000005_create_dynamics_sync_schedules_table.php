<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('erp-integration-hub.tables.sync_schedules', 'dynamics_sync_schedules'), function (Blueprint $table) {
            $table->id();
            $table->foreignId('sync_profile_id')->constrained('dynamics_sync_profiles')->cascadeOnDelete();

            $table->string('label')->nullable();
            $table->enum('frequency', [
                'every_minute', 'every_5_minutes', 'every_15_minutes', 'every_30_minutes',
                'hourly', 'every_6_hours', 'every_12_hours',
                'daily', 'weekly', 'monthly', 'custom'
            ])->default('hourly');

            $table->string('cron_expression')->default('0 * * * *');
            $table->string('timezone')->default('UTC');

            $table->boolean('is_active')->default(true);
            $table->timestamp('next_run_at')->nullable();
            $table->timestamp('last_run_at')->nullable();

            $table->json('options')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'next_run_at']);
            $table->index('sync_profile_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('erp-integration-hub.tables.sync_schedules', 'dynamics_sync_schedules'));
    }
};
