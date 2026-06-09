<?php

namespace Mostafax\ErpIntegrationHub\Security;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User;
use Mostafax\ErpIntegrationHub\Models\DynamicsConnection;
use Mostafax\ErpIntegrationHub\Models\SyncProfile;
use Mostafax\ErpIntegrationHub\Models\SyncLog;

class ErpPolicy
{
    use HandlesAuthorization;

    public function viewConnections(User $user): bool
    {
        return $user->can('view_connections');
    }

    public function manageConnections(User $user): bool
    {
        return $user->can('manage_connections');
    }

    public function viewSyncProfiles(User $user): bool
    {
        return $user->can('view_sync_profiles');
    }

    public function manageSyncProfiles(User $user): bool
    {
        return $user->can('manage_sync_profiles');
    }

    public function runSync(User $user, SyncProfile $profile = null): bool
    {
        return $user->can('run_sync');
    }

    public function cancelSync(User $user): bool
    {
        return $user->can('cancel_sync');
    }

    public function retrySync(User $user): bool
    {
        return $user->can('retry_sync');
    }

    public function viewLogs(User $user): bool
    {
        return $user->can('view_logs');
    }

    public function manageSettings(User $user): bool
    {
        return $user->can('manage_settings');
    }

    public function viewMonitoring(User $user): bool
    {
        return $user->can('view_monitoring');
    }
}
