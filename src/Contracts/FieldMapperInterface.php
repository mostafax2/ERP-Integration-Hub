<?php

namespace Mostafax\ErpIntegrationHub\Contracts;

interface FieldMapperInterface
{
    public function map(array $source): array;

    public function mapSingle(string $sourceField, mixed $value): mixed;

    public function preview(array $sampleRecord): array;

    public function validate(array $mappings): array;
}
