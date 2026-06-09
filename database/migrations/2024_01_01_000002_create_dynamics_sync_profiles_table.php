<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('erp-integration-hub.tables.sync_profiles', 'dynamics_sync_profiles'), function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->foreignId('connection_id')->constrained('dynamics_connections')->cascadeOnDelete();

            // Source (Laravel side)
            $table->string('source_model');        // App\Models\Student
            $table->string('source_table')->nullable();
            $table->json('source_filters')->nullable();
            $table->string('source_key')->default('id');

            // Destination (Dynamics side)
            $table->string('destination_entity');  // e.g. customers
            $table->string('destination_key')->default('No');
            $table->string('destination_company')->nullable();

            // Sync Mode
            $table->enum('sync_mode', [
                'manual', 'scheduled', 'realtime', 'incremental', 'full', 'event_driven'
            ])->default('manual');

            $table->enum('direction', ['push', 'pull', 'bidirectional'])->default('push');
            $table->enum('conflict_resolution', ['source_wins', 'destination_wins', 'manual'])->default('source_wins');

            // State Tracking
            $table->timestamp('last_synced_at')->nullable();
            $table->string('last_sync_cursor')->nullable(); // for incremental sync
            $table->enum('status', ['active', 'inactive', 'paused', 'error'])->default('active');
            $table->boolean('delete_on_remove')->default(false);

            $table->json('options')->nullable();
            $table->integer('priority')->default(50);
            $table->integer('chunk_size')->default(500);
            $table->integer('retry_limit')->default(3);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['status', 'sync_mode']);
            $table->index('connection_id');
            $table->index('source_model');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('erp-integration-hub.tables.sync_profiles', 'dynamics_sync_profiles'));
    }
};
