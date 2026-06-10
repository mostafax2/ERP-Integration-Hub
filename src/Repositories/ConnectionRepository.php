<?php

declare(strict_types=1);

namespace Mostafax\ErpIntegrationHub\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Mostafax\ErpIntegrationHub\Models\DynamicsConnection;

class ConnectionRepository
{
    public function all(): \Illuminate\Database\Eloquent\Collection
    {
        return DynamicsConnection::withCount('syncProfiles')->latest()->get();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return DynamicsConnection::withCount('syncProfiles')->latest()->paginate($perPage);
    }

    public function find(int $id): DynamicsConnection
    {
        return DynamicsConnection::findOrFail($id);
    }

    public function findBySlug(string $slug): DynamicsConnection
    {
        return DynamicsConnection::where('slug', $slug)->firstOrFail();
    }

    public function findDefault(): ?DynamicsConnection
    {
        return DynamicsConnection::where('is_default', true)->where('status', 'active')->first();
    }

    public function create(array $data): DynamicsConnection
    {
        return DynamicsConnection::create($data);
    }

    public function update(DynamicsConnection $connection, array $data): DynamicsConnection
    {
        $connection->update($data);
        return $connection->fresh();
    }

    public function delete(DynamicsConnection $connection): bool
    {
        return (bool) $connection->delete();
    }

    public function getActive(): \Illuminate\Database\Eloquent\Collection
    {
        return DynamicsConnection::active()->get();
    }
}
