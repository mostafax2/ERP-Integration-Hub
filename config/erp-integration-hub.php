<?php

return [

    /*
    |--------------------------------------------------------------------------
    | ERP Integration Hub Configuration
    |--------------------------------------------------------------------------
    */

    'name' => env('DYNAMICS_BRIDGE_NAME', 'ERP Integration Hub'),

    'version' => '1.0.0',

    /*
    |--------------------------------------------------------------------------
    | Allowed Custom Transformers
    | Only classes listed here can be used as custom_transformer in FieldMappings.
    | Register your own transformer classes: implement TransformerInterface.
    |--------------------------------------------------------------------------
    */
    'allowed_transformers' => [
        // Example: \App\Transformers\MyCustomTransformer::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    */
    'route_prefix' => env('DYNAMICS_BRIDGE_ROUTE_PREFIX', 'erp-integration-hub'),
    'api_prefix'   => env('ERP_INTEGRATION_HUB_API_PREFIX', 'api/erp-integration-hub'),
    'middleware'   => ['web', 'auth'],
    'api_middleware' => ['api', 'auth:sanctum'],

    /*
    |--------------------------------------------------------------------------
    | Microsoft Azure / Dynamics 365 OAuth
    |--------------------------------------------------------------------------
    */
    'microsoft' => [
        'authority_url'    => 'https://login.microsoftonline.com',
        'graph_url'        => 'https://graph.microsoft.com/v1.0',
        'token_endpoint'   => '/oauth2/v2.0/token',
        'authorize_endpoint' => '/oauth2/v2.0/authorize',
        'scopes'           => [
            'https://api.businesscentral.dynamics.com/.default',
        ],
        'cache_ttl'        => 3540, // 59 minutes — just under token expiry
    ],

    /*
    |--------------------------------------------------------------------------
    | Supported ERP Drivers
    |--------------------------------------------------------------------------
    */
    'drivers' => [
        'business_central' => [
            'label'    => 'Microsoft Dynamics 365 Business Central',
            'driver'   => \Mostafax\ErpIntegrationHub\Connections\Drivers\BusinessCentralDriver::class,
            'base_url' => 'https://api.businesscentral.dynamics.com/v2.0/{tenant_id}/{environment}/api/v2.0',
            'icon'     => 'dynamics-bc',
        ],
        'dynamics_finance' => [
            'label'    => 'Microsoft Dynamics 365 Finance',
            'driver'   => \Mostafax\ErpIntegrationHub\Connections\Drivers\DynamicsFinanceDriver::class,
            'base_url' => 'https://{environment}.operations.dynamics.com/data',
            'icon'     => 'dynamics-finance',
        ],
        'supply_chain' => [
            'label'    => 'Microsoft Dynamics 365 Supply Chain Management',
            'driver'   => \Mostafax\ErpIntegrationHub\Connections\Drivers\SupplyChainDriver::class,
            'base_url' => 'https://{environment}.operations.dynamics.com/data',
            'icon'     => 'dynamics-scm',
        ],
        'sap' => [
            'label'    => 'SAP S/4HANA',
            'driver'   => \Mostafax\ErpIntegrationHub\Connections\Drivers\SapDriver::class,
            'base_url' => 'https://{hostname}:443/sap/opu/odata/sap',
            'icon'     => 'sap',
        ],
        'odoo' => [
            'label'    => 'Odoo',
            'driver'   => \Mostafax\ErpIntegrationHub\Connections\Drivers\OdooDriver::class,
            'base_url' => 'https://your-odoo.odoo.com',
            'icon'     => 'odoo',
        ],
        'erpnext' => [
            'label'    => 'ERPNext',
            'driver'   => \Mostafax\ErpIntegrationHub\Connections\Drivers\ErpNextDriver::class,
            'base_url' => 'https://your-erpnext.com/api/resource',
            'icon'     => 'erpnext',
        ],
        'custom' => [
            'label'    => 'Custom REST / API',
            'driver'   => \Mostafax\ErpIntegrationHub\Connections\Drivers\CustomDriver::class,
            'base_url' => 'https://api.example.com',
            'icon'     => 'custom',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync Configuration
    |--------------------------------------------------------------------------
    */
    'sync' => [
        'default_chunk_size'   => 500,
        'max_chunk_size'       => 5000,
        'default_retry_limit'  => 3,
        'retry_delay_seconds'  => 60,
        'timeout_seconds'      => 300,
        'parallel_workers'     => 4,
        'use_lazy_collections' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    */
    'queues' => [
        'default'  => env('DYNAMICS_BRIDGE_QUEUE', 'dynamics-sync'),
        'high'     => env('DYNAMICS_BRIDGE_QUEUE_HIGH', 'dynamics-sync-high'),
        'low'      => env('DYNAMICS_BRIDGE_QUEUE_LOW', 'dynamics-sync-low'),
        'monitor'  => env('DYNAMICS_BRIDGE_QUEUE_MONITOR', 'dynamics-monitor'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'driver'     => env('DYNAMICS_BRIDGE_CACHE_DRIVER', 'redis'),
        'prefix'     => 'dynamics_bridge',
        'ttl'        => 300,
        'token_ttl'  => 3540,
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Tables
    |--------------------------------------------------------------------------
    */
    'tables' => [
        'connections'   => 'dynamics_connections',
        'sync_profiles' => 'dynamics_sync_profiles',
        'field_mappings' => 'dynamics_field_mappings',
        'sync_logs'     => 'dynamics_sync_logs',
        'sync_schedules' => 'dynamics_sync_schedules',
        'failed_syncs'  => 'dynamics_failed_syncs',
        'settings'      => 'dynamics_settings',
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    */
    'security' => [
        'encrypt_credentials' => true,
        'audit_logging'       => true,
        'multi_tenant'        => false,
        'tenant_column'       => 'tenant_id',
        'guard'               => env('DYNAMICS_BRIDGE_GUARD', 'web'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'enabled'  => true,
        'channels' => ['database', 'mail'],
        'slack_webhook' => env('DYNAMICS_BRIDGE_SLACK_WEBHOOK'),
        'mail_to' => env('DYNAMICS_BRIDGE_NOTIFY_EMAIL'),
        'events'  => [
            'sync_completed' => true,
            'sync_failed'    => true,
            'connection_lost' => true,
            'queue_overloaded' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Background Processing Engine Integration
    |--------------------------------------------------------------------------
    */
    'bpe' => [
        'enabled'     => true,
        'batch_size'  => 100,
        'concurrency' => 4,
        'timeout'     => 600,
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring
    |--------------------------------------------------------------------------
    */
    'monitoring' => [
        'enabled'         => true,
        'refresh_interval' => 5000, // milliseconds
        'retention_days'  => 90,
        'alert_threshold' => [
            'failed_jobs'        => 10,
            'queue_size'         => 1000,
            'processing_time_ms' => 30000,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Detection
    |--------------------------------------------------------------------------
    */
    'detection' => [
        'scan_paths' => [
            app_path('Models'),
            base_path('Modules'),
            base_path('packages'),
        ],
        'exclude_models' => [
            'User',
            'PersonalAccessToken',
            'FailedJob',
            'PasswordResetToken',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | UI / Frontend
    |--------------------------------------------------------------------------
    */
    'ui' => [
        'default_locale' => 'en',
        'supported_locales' => ['en', 'ar'],
        'default_theme' => 'light',
        'rtl_locales' => ['ar'],
        'brand_name' => env('DYNAMICS_BRIDGE_BRAND', 'ERP Integration Hub'),
        'brand_logo' => null,
    ],

];
