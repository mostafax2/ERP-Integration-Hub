<?php

declare(strict_types=1);

namespace Mostafax\ErpIntegrationHub\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Mostafax\ErpIntegrationHub\Models\SyncProfile;

class SyncProfileRepository
{
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = SyncProfile::with('connection')
            ->withCount(['syncLogs', 'failedSyncs'])
            ->latest();

        if ($status = $filters['status'] ?? null) {
            $query->where('status', $status);
        }

        if ($connectionId = $filters['connection_id'] ?? null) {
            $query->where('connection_id', $connectionId);
        }

        if ($search = $filters['search'] ?? null) {
            $query->where(fn($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('source_model', 'like', "%{$search}%")
            );
        }

        return $query->paginate($perPage);
    }

    public function find(int $id): SyncProfile
    {
        return SyncProfile::with(['connection', 'allFieldMappings', 'schedules'])->findOrFail($id);
    }

    public function create(array $data): SyncProfile
    {
        return SyncProfile::create($data);
    }

    public function update(SyncProfile $profile, array $data): SyncProfile
    {
        $profile->update($data);
        return $profile->fresh(['connection', 'allFieldMappings']);
    }

    public function delete(SyncProfile $profile): bool
    {
        return (bool) $profile->delete();
    }

    public function getActiveForScheduler(): \Illuminate\Database\Eloquent\Collection
    {
        return SyncProfile::active()
            ->whereHas('schedules', fn($q) => $q->active())
            ->with(['connection', 'schedules'])
            ->get();
    }
}
