<?php

namespace Mostafax\ErpIntegrationHub\DTOs;

final class SyncResultDTO
{
    public function __construct(
        public readonly bool $success,
        public readonly int $totalRecords = 0,
        public readonly int $processedRecords = 0,
        public readonly int $successRecords = 0,
        public readonly int $failedRecords = 0,
        public readonly int $skippedRecords = 0,
        public readonly int $durationMs = 0,
        public readonly array $errors = [],
        public readonly ?string $batchId = null,
        public readonly ?string $message = null,
    ) {}

    public static function success(
        int $total,
        int $succeeded,
        int $failed = 0,
        int $skipped = 0,
        int $durationMs = 0,
        array $errors = [],
        ?string $batchId = null,
    ): self {
        return new self(
            success: $failed === 0,
            totalRecords: $total,
            processedRecords: $succeeded + $failed,
            successRecords: $succeeded,
            failedRecords: $failed,
            skippedRecords: $skipped,
            durationMs: $durationMs,
            errors: $errors,
            batchId: $batchId,
        );
    }

    public static function failure(string $message, array $errors = []): self
    {
        return new self(
            success: false,
            message: $message,
            errors: $errors,
        );
    }

    public function toArray(): array
    {
        return [
            'success'           => $this->success,
            'total_records'     => $this->totalRecords,
            'processed_records' => $this->processedRecords,
            'success_records'   => $this->successRecords,
            'failed_records'    => $this->failedRecords,
            'skipped_records'   => $this->skippedRecords,
            'duration_ms'       => $this->durationMs,
            'errors'            => $this->errors,
            'batch_id'          => $this->batchId,
            'message'           => $this->message,
        ];
    }
}
