<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('erp-integration-hub.tables.failed_syncs', 'dynamics_failed_syncs'), function (Blueprint $table) {
            $table->id();
            $table->foreignId('sync_log_id')->nullable()->constrained('dynamics_sync_logs')->nullOnDelete();
            $table->foreignId('sync_profile_id')->nullable()->constrained('dynamics_sync_profiles')->nullOnDelete();

            $table->string('record_id')->nullable();
            $table->string('record_type')->nullable();
            $table->json('record_data')->nullable();

            $table->text('error_message');
            $table->text('stack_trace')->nullable();
            $table->string('error_code')->nullable();

            $table->unsignedTinyInteger('attempt_count')->default(1);
            $table->unsignedTinyInteger('max_attempts')->default(3);

            $table->enum('status', ['pending_retry', 'retrying', 'resolved', 'abandoned'])->default('pending_retry');

            $table->timestamp('last_attempted_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();

            $table->timestamps();

            $table->index(['status', 'attempt_count']);
            $table->index('sync_profile_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('erp-integration-hub.tables.failed_syncs', 'dynamics_failed_syncs'));
    }
};
