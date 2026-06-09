<div align="center">

<img src="https://raw.githubusercontent.com/mostafax2/ERP-Integration-Hub/main/docs/assets/logo.svg" alt="ERP Integration Hub" width="80" height="80" />

# ERP Integration Hub

**Enterprise Integration Platform for ERP Systems**

[![Laravel](https://img.shields.io/badge/Laravel-10%2F11%2F12-FF2D20?logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-%5E8.2-777BB4?logo=php)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green)](LICENSE)
[![Packagist](https://img.shields.io/badge/Packagist-mostafax%2Ferp--integration--hub-F28D1A?logo=packagist)](https://packagist.org/packages/mostafax/erp-integration-hub)

**[📖 Full Documentation →](https://mostafax2.github.io/ERP-Integration-Hub/index.html)**　　**[📖 التوثيق بالعربية →](https://mostafax2.github.io/ERP-Integration-Hub/ar/index.html)**

</div>

---

## Overview

ERP Integration Hub is a Laravel package that connects your application to multiple ERP systems through a visual no-code interface — no manual API calls, no custom glue code.

```
ERP Integration Hub
├── Dynamics 365 Business Central  ✓
├── Dynamics 365 Finance            ✓
├── Supply Chain Management         ✓
├── SAP S/4HANA                     ✓
├── Odoo                            ✓
├── ERPNext                         ✓
└── Custom REST / API               ✓
```

## Features

- **Visual Field Mapping** — drag-and-drop builder with 9 built-in transformers and auto-map
- **6 Sync Modes** — manual, scheduled, real-time, incremental, full, event-driven
- **Sync Scheduler** — cron expressions, per-timezone, without overlapping
- **Real-Time Monitoring** — live dashboard with queue health and success rates
- **Retry Management** — per-record, per-profile, or bulk retry of failed jobs
- **Role-Based Security** — 10 granular permissions, 3 default roles
- **REST API** — full API layer with Sanctum authentication
- **Vue 3 SPA** — dark/light mode, Arabic RTL + English i18n
- **High Performance** — lazy collections + chunked processing, 1M–100M records

## Requirements

| Dependency | Version |
|------------|---------|
| PHP | ^8.2 |
| Laravel | 10 / 11 / 12 |
| Redis | recommended |
| `mostafax/background-processing-engine` | ^1.0 |

## Installation

```bash
composer require mostafax/erp-integration-hub
```

Run the installer (publishes config, migrations, assets, and seeds default roles):

```bash
php artisan erp-integration-hub:install
```

Start the sync worker:

```bash
php artisan queue:work --queue=dynamics-sync
```

Open the dashboard:

```
https://your-app.com/erp-integration-hub
```

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=erp-integration-hub-config
```

Key settings in `config/erp-integration-hub.php`:

```php
'drivers' => [
    'business_central' => [...],
    'dynamics_finance' => [...],
    'supply_chain'     => [...],
    'sap'              => [...],
    'odoo'             => [...],
    'erpnext'          => [...],
    'custom'           => [...],
],

'queue' => [
    'connection' => env('QUEUE_CONNECTION', 'redis'),
    'name'       => 'dynamics-sync',
    'workers'    => env('ERP_SYNC_WORKERS', 3),
],
```

## REST API

All endpoints are prefixed with `/api/erp-integration-hub` and require Sanctum authentication.

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/connections` | List ERP connections |
| `POST` | `/connections` | Create a connection |
| `POST` | `/connections/{id}/test` | Test credentials |
| `GET` | `/sync-profiles` | List sync profiles |
| `POST` | `/sync/run/{profileId}` | Run a sync |
| `GET` | `/monitoring/dashboard` | Dashboard stats |
| `GET` | `/logs` | Sync logs |
| `POST` | `/sync/retry-all` | Retry all failed jobs |

> Full API reference in the [documentation](https://mostafax2.github.io/ERP-Integration-Hub/index.html).

## Artisan Commands

```bash
# Install the package (first-time setup)
php artisan erp-integration-hub:install

# Run a sync profile manually
php artisan erp-integration-hub:sync {profileId}

# Process due scheduled syncs
php artisan erp-integration-hub:schedule-run
```

## Security & Permissions

The package ships with `spatie/laravel-permission` integration and 3 default roles:

| Role | Permissions |
|------|-------------|
| `erp_admin` | Full access |
| `erp_operator` | Manage syncs and connections |
| `erp_viewer` | Read-only access |

## Architecture

```
src/
├── Actions/           # Single-responsibility business actions
├── Authentication/    # Microsoft OAuth client + token manager
├── Connections/
│   ├── Drivers/       # ERP-specific drivers (OData, JSON-RPC, REST)
│   └── ErpConnectionManager.php
├── Console/Commands/  # Artisan commands
├── Contracts/         # Interfaces
├── DTOs/              # Data Transfer Objects
├── Events/            # Sync lifecycle events
├── Exceptions/
├── Facades/           # ErpIntegrationHub facade
├── FieldMapping/      # Mapping engine + 9 transformers
├── Http/              # Controllers, Requests, Resources
├── Jobs/              # Queue jobs
├── Models/            # Eloquent models
├── Monitoring/        # Dashboard stats service
├── Notifications/     # Mail / Slack / database
├── Providers/         # Service provider
├── Repositories/
├── Scheduler/
├── Security/          # Policy + permission manager
└── Services/          # Sync orchestrator
```

## Documentation

| Language | Link |
|----------|------|
| English | [https://mostafax2.github.io/ERP-Integration-Hub/index.html](https://mostafax2.github.io/ERP-Integration-Hub/index.html) |
| العربية | [https://mostafax2.github.io/ERP-Integration-Hub/ar/index.html](https://mostafax2.github.io/ERP-Integration-Hub/ar/index.html) |

## License

MIT — © [Mostafa Elbayyar](mailto:mostafa.m.elbiar2@gmail.com)
