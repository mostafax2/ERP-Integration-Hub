<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('erp-integration-hub.tables.sync_logs', 'dynamics_sync_logs'), function (Blueprint $table) {
            $table->id();
            $table->string('batch_id')->nullable()->index(); // links to BPE batch
            $table->foreignId('sync_profile_id')->nullable()->constrained('dynamics_sync_profiles')->nullOnDelete();
            $table->foreignId('connection_id')->nullable()->constrained('dynamics_connections')->nullOnDelete();

            $table->enum('trigger', ['manual', 'scheduled', 'event', 'api', 'retry'])->default('manual');
            $table->enum('status', ['pending', 'running', 'completed', 'failed', 'cancelled', 'partial'])->default('pending');

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();

            $table->unsignedBigInteger('total_records')->default(0);
            $table->unsignedBigInteger('processed_records')->default(0);
            $table->unsignedBigInteger('success_records')->default(0);
            $table->unsignedBigInteger('failed_records')->default(0);
            $table->unsignedBigInteger('skipped_records')->default(0);

            $table->json('summary')->nullable();
            $table->json('errors')->nullable();
            $table->text('message')->nullable();

            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();

            $table->timestamps();

            $table->index(['sync_profile_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index('batch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('erp-integration-hub.tables.sync_logs', 'dynamics_sync_logs'));
    }
};
