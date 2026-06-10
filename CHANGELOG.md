# Changelog

All notable changes to `mostafax/erp-integration-hub` will be documented here.

This project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.1.0] — 2026-06-10

### Added
- **4 new ERP drivers**: SAP S/4HANA (Basic/Bearer auth), Odoo (JSON-RPC 2.0), ERPNext (Token auth), Custom REST/API — completing the 7-driver target
- `allowed_transformers` config key — whitelist for custom field transformer classes
- `SyncLogRepository::cursor()` — streaming export via `LazyCollection` for unbounded record sets
- Postman Collection v2.1 — 46 endpoints with full examples, Arabic descriptions, auto-token script
- `declare(strict_types=1)` enforced across all 82 PHP source files (PSR-12)

### Changed
- `AbstractDynamicsDriver` now exposes a `protected string $authScheme` property; all HTTP methods use it instead of hardcoding `"Bearer"`
- `SapDriver::connect()` supports `basic` (default) and `bearer` auth; delegates to `$authScheme`
- `ErpNextDriver` sets `$authScheme = 'token'` to match ERPNext's Token auth spec
- `CustomDriver` resolves auth via `match` (basic / api_key / bearer / none)
- `ConnectionRequest` makes `tenant_id` required only for Microsoft drivers (business_central, dynamics_finance, supply_chain)
- `SyncProfileRequest` validates `source_filters.*.operator` against a safe whitelist
- `LogController::export()` switched from `paginate(1000)` to cursor-based `StreamedResponse`
- `SettingsController::index()` now filters `where('is_public', true)` — was previously exposing all settings

### Security
- **SQL injection** — `SyncOrchestrator` validates filter operators against `ALLOWED_FILTER_OPERATORS` constant before passing to query builder
- **Code injection** — `FieldMappingEngine::applyCustomTransformer()` enforces `allowed_transformers` whitelist; rejects unregistered classes with a clear exception
- **Authorization** — all 46 API controller actions now call `$this->authorize()` with the correct permission gate
- **CSV formula injection** — `LogController::sanitizeCsvValue()` strips `= + - @` prefixes from exported values
- **Model reflection** — `SyncProfileController::analyzeModel()` validates the class against `detect-models` whitelist before reflecting

### Fixed
- `SyncOrchestrator` now checks `$driver->connect()` return value and throws `SyncException` on auth failure (was silently continuing)
- `ErpNextDriver` removed dead `resolveHeaders()` method that was never invoked by the abstract driver
- Double blank lines after `declare(strict_types=1)` normalized to a single blank line across all files

---

## [1.0.0] — 2026-05-20

### Added
- Initial release
- Microsoft Dynamics 365 Business Central driver (OData v4 + Azure OAuth2)
- Microsoft Dynamics 365 Finance & Operations driver
- Microsoft Dynamics 365 Supply Chain Management driver
- Visual field mapping engine with 10 built-in transformers (uppercase, lowercase, trim, date_format, number_format, boolean, concatenate, split, lookup, custom)
- Sync profiles with incremental / full / manual / scheduled modes
- Laravel Queue integration (`RunSyncJob`, `RetryFailedSyncJob`)
- Redis token caching (59-minute TTL for Microsoft OAuth tokens)
- Sync scheduler with Cron expression support
- Real-time monitoring dashboard and health check
- Sync log export (CSV)
- Settings management
- Spatie Laravel Permission integration (erp_admin, erp_operator, erp_viewer)
- Laravel Sanctum API authentication

---

## Upgrade Guide

### From 1.0.x → 1.1.0

1. **Run migrations** — no new migrations in this release.

2. **Publish updated config** to pick up the new `allowed_transformers` and new driver entries:
   ```bash
   php artisan vendor:publish --tag=erp-integration-hub-config --force
   ```

3. **Register permissions** — new gates have been added. Re-seed or add these permissions:
   `view_connections`, `manage_connections`, `view_sync_profiles`, `manage_sync_profiles`,
   `run_sync`, `cancel_sync`, `retry_sync`, `view_logs`, `view_monitoring`, `manage_settings`

   ```bash
   php artisan erp-integration-hub:install
   ```

4. **Custom transformers** — if you were using `custom_transformer` in field mappings, register those classes in config:
   ```php
   // config/erp-integration-hub.php
   'allowed_transformers' => [
       \App\Transformers\MyCustomTransformer::class,
   ],
   ```

5. **SAP / Odoo / ERPNext connections** — if using these drivers, ensure `extra_config` is set correctly:
   - SAP: `extra_config.auth_type` = `basic` (default) or `bearer`
   - Odoo: `extra_config.database` = your Odoo database name
   - ERPNext: uses `client_id` as API key and `client_secret` as API secret

---

[1.1.0]: https://github.com/mostafax2/ERP-Integration-Hub/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/mostafax2/ERP-Integration-Hub/releases/tag/v1.0.0
