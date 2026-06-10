<?php

declare(strict_types=1);

namespace Mostafax\ErpIntegrationHub\Security;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionManager
{
    public const PERMISSIONS = [
        'view_connections',
        'manage_connections',
        'view_sync_profiles',
        'manage_sync_profiles',
        'run_sync',
        'cancel_sync',
        'retry_sync',
        'view_logs',
        'manage_settings',
        'view_monitoring',
    ];

    public const DEFAULT_ROLES = [
        'erp_admin' => [
            'view_connections', 'manage_connections',
            'view_sync_profiles', 'manage_sync_profiles',
            'run_sync', 'cancel_sync', 'retry_sync',
            'view_logs', 'manage_settings', 'view_monitoring',
        ],
        'erp_operator' => [
            'view_connections',
            'view_sync_profiles',
            'run_sync', 'retry_sync',
            'view_logs', 'view_monitoring',
        ],
        'erp_viewer' => [
            'view_connections',
            'view_sync_profiles',
            'view_logs',
            'view_monitoring',
        ],
    ];

    public function createPermissions(): void
    {
        $guard = config('erp-integration-hub.security.guard', 'web');
        foreach (self::PERMISSIONS as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => $guard]);
        }
    }

    public function createDefaultRoles(): void
    {
        $guard = config('erp-integration-hub.security.guard', 'web');
        foreach (self::DEFAULT_ROLES as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => $guard]);
            $role->syncPermissions($permissions);
        }
    }

    public function seedAll(): void
    {
        $this->createPermissions();
        $this->createDefaultRoles();
    }

    public static function all(): array
    {
        return self::PERMISSIONS;
    }
}
