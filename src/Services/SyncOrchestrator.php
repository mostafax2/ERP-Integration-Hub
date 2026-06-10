<?php

declare(strict_types=1);

namespace Mostafax\ErpIntegrationHub\Services;

use Illuminate\Support\LazyCollection;
use Mostafax\ErpIntegrationHub\Connections\ErpConnectionManager;
use Mostafax\ErpIntegrationHub\DTOs\SyncResultDTO;
use Mostafax\ErpIntegrationHub\Events\SyncCompleted;
use Mostafax\ErpIntegrationHub\Events\SyncFailed;
use Mostafax\ErpIntegrationHub\Exceptions\SyncException;
use Mostafax\ErpIntegrationHub\FieldMapping\FieldMappingEngine;
use Mostafax\ErpIntegrationHub\Models\FailedSync;
use Mostafax\ErpIntegrationHub\Models\SyncLog;
use Mostafax\ErpIntegrationHub\Models\SyncProfile;

class SyncOrchestrator
{
    private const ALLOWED_FILTER_OPERATORS = [
        '=', '!=', '<>', '>', '<', '>=', '<=', 'like', 'not like', 'in', 'not in',
    ];

    public function __construct(
        private readonly ErpConnectionManager $connectionManager
    ) {}

    public function run(SyncProfile $profile, SyncLog $log): SyncResultDTO
    {
        $log->markAsStarted();
        $startTime = microtime(true);

        try {
            $connection = $profile->connection;
            if (! $connection || ! $connection->isActive()) {
                throw new SyncException("Connection [{$connection?->name}] is not active.");
            }

            $driver = $this->connectionManager->driver($connection);
            if (! $driver->connect()) {
                throw new SyncException("Failed to authenticate with ERP [{$connection->name}]. Check credentials.");
            }

            $mapper      = new FieldMappingEngine($profile);
            $chunkSize   = $profile->chunk_size ?: config('erp-integration-hub.sync.default_chunk_size', 500);

            $stats = ['total' => 0, 'success' => 0, 'failed' => 0, 'skipped' => 0];

            $this->streamSourceRecords($profile)->chunk($chunkSize)->each(
                function ($chunk) use ($profile, $driver, $mapper, $log, &$stats) {
                    foreach ($chunk as $record) {
                        $stats['total']++;
                        try {
                            $mapped = $mapper->map($record->toArray());
                            $driver->upsertRecord(
                                $profile->destination_entity,
                                $mapped,
                                $profile->destination_key
                            );
                            $stats['success']++;
                        } catch (\Throwable $e) {
                            $stats['failed']++;
                            $this->recordFailure($log, $profile, $record, $e);
                        }
                    }
                }
            );

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            $log->markAsCompleted([
                'total_records'     => $stats['total'],
                'processed_records' => $stats['success'] + $stats['failed'],
                'success_records'   => $stats['success'],
                'failed_records'    => $stats['failed'],
                'skipped_records'   => $stats['skipped'],
                'duration_ms'       => $durationMs,
            ]);

            $profile->update(['last_synced_at' => now()]);

            $result = SyncResultDTO::success(
                $stats['total'], $stats['success'], $stats['failed'],
                $stats['skipped'], $durationMs,
            );

            event(new SyncCompleted($profile, $log, $result));

            return $result;
        } catch (\Throwable $e) {
            $log->markAsFailed($e->getMessage());
            event(new SyncFailed($profile, $log, $e));
            throw $e;
        }
    }

    private function streamSourceRecords(SyncProfile $profile): LazyCollection
    {
        $modelClass = $profile->source_model;

        if (! class_exists($modelClass)) {
            throw new SyncException("Source model [{$modelClass}] does not exist.");
        }

        $query = $modelClass::query();

        foreach ($profile->source_filters ?? [] as $filter) {
            $op = strtolower((string) ($filter['operator'] ?? '='));
            if (! in_array($op, self::ALLOWED_FILTER_OPERATORS, strict: true)) {
                throw new SyncException("Disallowed filter operator [{$op}] in sync profile [{$profile->name}].");
            }
            $query->where($filter['field'], $op, $filter['value']);
        }

        // Incremental sync: only changed records since last sync
        if ($profile->sync_mode === 'incremental' && $profile->last_synced_at) {
            $query->where('updated_at', '>', $profile->last_synced_at);
        }

        return $query->lazyById(config('erp-integration-hub.sync.default_chunk_size', 500));
    }

    private function recordFailure(SyncLog $log, SyncProfile $profile, mixed $record, \Throwable $e): void
    {
        FailedSync::create([
            'sync_log_id'      => $log->id,
            'sync_profile_id'  => $profile->id,
            'record_id'        => method_exists($record, 'getKey') ? $record->getKey() : null,
            'record_type'      => $profile->source_model,
            'record_data'      => method_exists($record, 'toArray') ? $record->toArray() : (array) $record,
            'error_message'    => $e->getMessage(),
            'stack_trace'      => $e->getTraceAsString(),
            'max_attempts'     => $profile->retry_limit,
        ]);
    }
}
