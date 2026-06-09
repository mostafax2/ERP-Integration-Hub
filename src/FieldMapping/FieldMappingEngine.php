<?php

namespace Mostafax\ErpIntegrationHub\FieldMapping;

use Mostafax\ErpIntegrationHub\Contracts\FieldMapperInterface;
use Mostafax\ErpIntegrationHub\DTOs\FieldMappingDTO;
use Mostafax\ErpIntegrationHub\FieldMapping\Transformers\TransformerFactory;
use Mostafax\ErpIntegrationHub\Models\SyncProfile;

class FieldMappingEngine implements FieldMapperInterface
{
    private array $mappings = [];

    public function __construct(private SyncProfile $profile)
    {
        $this->mappings = $profile->fieldMappings()
            ->get()
            ->map(fn($m) => FieldMappingDTO::fromArray($m->toArray()))
            ->all();
    }

    public function map(array $source): array
    {
        $result = [];

        foreach ($this->mappings as $mapping) {
            if ($mapping->isIgnored) {
                continue;
            }

            $value = data_get($source, $mapping->sourceField);

            if ($value === null && $mapping->defaultValue !== null) {
                $value = $mapping->defaultValue;
            }

            if ($value === null && $mapping->isRequired) {
                throw new \InvalidArgumentException(
                    "Required field [{$mapping->sourceField}] is missing in source record."
                );
            }

            $result[$mapping->destinationField] = $this->applyTransformation($mapping, $value);
        }

        return $result;
    }

    public function mapSingle(string $sourceField, mixed $value): mixed
    {
        foreach ($this->mappings as $mapping) {
            if ($mapping->sourceField === $sourceField) {
                return $this->applyTransformation($mapping, $value);
            }
        }
        return $value;
    }

    public function preview(array $sampleRecord): array
    {
        $errors = [];
        try {
            $mapped = $this->map($sampleRecord);
            return ['success' => true, 'mapped' => $mapped, 'source' => $sampleRecord];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage(), 'source' => $sampleRecord];
        }
    }

    public function validate(array $mappings): array
    {
        $errors = [];
        foreach ($mappings as $idx => $mapping) {
            if (empty($mapping['source_field'])) {
                $errors[] = "Row #{$idx}: source_field is required.";
            }
            if (empty($mapping['destination_field'])) {
                $errors[] = "Row #{$idx}: destination_field is required.";
            }
        }
        return $errors;
    }

    public function getKeyFields(): array
    {
        return array_filter($this->mappings, fn($m) => $m->isKeyField);
    }

    private function applyTransformation(FieldMappingDTO $mapping, mixed $value): mixed
    {
        if ($mapping->transformation === 'none' || $value === null) {
            return $value;
        }

        if ($mapping->transformation === 'custom' && $mapping->customTransformer) {
            return $this->applyCustomTransformer($mapping->customTransformer, $value, $mapping);
        }

        return TransformerFactory::make($mapping->transformation)
            ->transform($value, $mapping->transformationConfig);
    }

    private function applyCustomTransformer(string $transformer, mixed $value, FieldMappingDTO $mapping): mixed
    {
        if (class_exists($transformer)) {
            return app($transformer)->transform($value, $mapping->transformationConfig);
        }
        return $value;
    }
}
