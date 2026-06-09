<?php

namespace Mostafax\ErpIntegrationHub\Contracts;

use Mostafax\ErpIntegrationHub\DTOs\SyncResultDTO;

interface SyncProfileInterface
{
    public function run(array $options = []): SyncResultDTO;

    public function runChunk(array $records, array $options = []): SyncResultDTO;

    public function cancel(): bool;

    public function getProgress(): array;

    public function estimateCount(): int;
}
