<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mostafax\ErpIntegrationHub\Actions\RunSyncAction;
use Mostafax\ErpIntegrationHub\Jobs\RunSyncJob;
use Mostafax\ErpIntegrationHub\Models\DynamicsConnection;
use Mostafax\ErpIntegrationHub\Models\SyncProfile;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
    $this->user = \App\Models\User::factory()->create();
    $this->actingAs($this->user);
});

test('running a sync profile queues a job', function () {
    $connection = DynamicsConnection::factory()->create(['status' => 'active']);
    $profile    = SyncProfile::factory()->create([
        'connection_id' => $connection->id,
        'status'        => 'active',
    ]);

    $action = app(RunSyncAction::class);
    $result = $action->execute($profile, 'manual', true, $this->user);

    expect($result['success'])->toBeTrue();
    Queue::assertPushed(RunSyncJob::class);
});

test('running a sync via API returns log_id', function () {
    $connection = DynamicsConnection::factory()->create(['status' => 'active']);
    $profile    = SyncProfile::factory()->create([
        'connection_id' => $connection->id,
        'status'        => 'active',
    ]);

    $this->postJson("/api/erp-integration-hub/sync/run/{$profile->id}", ['async' => true])
        ->assertOk()
        ->assertJsonStructure(['log_id', 'success']);
});
