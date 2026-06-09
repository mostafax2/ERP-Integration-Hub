<?php

namespace Mostafax\ErpIntegrationHub\DTOs;

final class FieldMappingDTO
{
    public function __construct(
        public readonly string $sourceField,
        public readonly string $destinationField,
        public readonly string $transformation = 'none',
        public readonly array $transformationConfig = [],
        public readonly mixed $defaultValue = null,
        public readonly bool $isRequired = false,
        public readonly bool $isIgnored = false,
        public readonly bool $isKeyField = false,
        public readonly ?string $customTransformer = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            sourceField: $data['source_field'],
            destinationField: $data['destination_field'],
            transformation: $data['transformation'] ?? 'none',
            transformationConfig: $data['transformation_config'] ?? [],
            defaultValue: $data['default_value'] ?? null,
            isRequired: $data['is_required'] ?? false,
            isIgnored: $data['is_ignored'] ?? false,
            isKeyField: $data['is_key_field'] ?? false,
            customTransformer: $data['custom_transformer'] ?? null,
        );
    }
}
