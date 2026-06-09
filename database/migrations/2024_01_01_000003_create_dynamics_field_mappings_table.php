<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('erp-integration-hub.tables.field_mappings', 'dynamics_field_mappings'), function (Blueprint $table) {
            $table->id();
            $table->foreignId('sync_profile_id')->constrained('dynamics_sync_profiles')->cascadeOnDelete();

            $table->string('source_field');
            $table->string('destination_field');

            $table->enum('transformation', [
                'none', 'uppercase', 'lowercase', 'trim', 'date_format',
                'number_format', 'boolean', 'custom', 'lookup', 'concatenate', 'split'
            ])->default('none');

            $table->json('transformation_config')->nullable(); // e.g. {"format": "Y-m-d", "locale": "en"}
            $table->string('default_value')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_ignored')->default(false);
            $table->boolean('is_key_field')->default(false);    // used for upsert matching

            $table->text('custom_transformer')->nullable();     // closure expression / class name

            $table->integer('sort_order')->default(0);
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['sync_profile_id', 'is_ignored']);
            $table->index('source_field');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('erp-integration-hub.tables.field_mappings', 'dynamics_field_mappings'));
    }
};
