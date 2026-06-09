<?php

namespace Mostafax\ErpIntegrationHub\FieldMapping\Transformers;

use Mostafax\ErpIntegrationHub\Contracts\TransformerInterface;

class LowercaseTransformer implements TransformerInterface
{
    public function transform(mixed $value, array $config = []): mixed
    {
        return is_string($value) ? strtolower($value) : $value;
    }
}
