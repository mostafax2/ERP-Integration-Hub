<?php

namespace Mostafax\ErpIntegrationHub\Contracts;

interface ConnectionInterface
{
    public function connect(): bool;

    public function disconnect(): void;

    public function test(): array;

    public function isConnected(): bool;

    public function getStatus(): string;

    public function fetchEntities(): array;

    public function fetchEntityFields(string $entity): array;

    public function fetchRecords(string $entity, array $filters = [], int $top = 100, string $skip = null): array;

    public function upsertRecord(string $entity, array $data, string $keyField = null): array;

    public function deleteRecord(string $entity, string $id): bool;

    public function getMetadata(): array;
}
