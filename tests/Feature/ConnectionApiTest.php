<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Mostafax\ErpIntegrationHub\Models\DynamicsConnection;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = \App\Models\User::factory()->create();
    $this->actingAs($this->user);
});

test('can list connections via API', function () {
    DynamicsConnection::factory()->count(3)->create();

    $this->getJson('/api/erp-integration-hub/connections')
        ->assertOk()
        ->assertJsonStructure(['data']);
});

test('can create a connection', function () {
    $this->postJson('/api/erp-integration-hub/connections', [
        'name'          => 'Test BC Connection',
        'driver'        => 'business_central',
        'tenant_id'     => 'tenant-123',
        'client_id'     => 'client-456',
        'client_secret' => 'secret-789',
        'environment_name' => 'production',
        'test_after_create' => false,
    ])->assertCreated()->assertJsonPath('data.name', 'Test BC Connection');
});

test('connection name is required', function () {
    $this->postJson('/api/erp-integration-hub/connections', [
        'driver'        => 'business_central',
        'tenant_id'     => 'tenant-123',
        'client_id'     => 'client-456',
        'client_secret' => 'secret-789',
    ])->assertUnprocessable();
});

test('can delete a connection', function () {
    $conn = DynamicsConnection::factory()->create();

    $this->deleteJson("/api/erp-integration-hub/connections/{$conn->id}")
        ->assertOk();

    $this->assertSoftDeleted('dynamics_connections', ['id' => $conn->id]);
});
