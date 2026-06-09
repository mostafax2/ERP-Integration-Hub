<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('erp-integration-hub.tables.connections', 'dynamics_connections'), function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('driver')->default('business_central');
            $table->string('environment_name')->nullable();
            $table->string('tenant_id');
            $table->string('client_id');
            $table->text('client_secret'); // encrypted
            $table->string('base_url')->nullable();
            $table->string('company_id')->nullable();
            $table->json('extra_config')->nullable();
            $table->enum('status', ['active', 'inactive', 'error', 'testing'])->default('inactive');
            $table->string('status_message')->nullable();
            $table->timestamp('last_connected_at')->nullable();
            $table->timestamp('last_tested_at')->nullable();
            $table->boolean('is_default')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('tenant_id_ref')->nullable()->comment('for multi-tenant setups');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['status', 'driver']);
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('erp-integration-hub.tables.connections', 'dynamics_connections'));
    }
};
