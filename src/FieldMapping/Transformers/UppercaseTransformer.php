<?php

declare(strict_types=1);

namespace Mostafax\ErpIntegrationHub\FieldMapping\Transformers;

use Mostafax\ErpIntegrationHub\Contracts\TransformerInterface;

class UppercaseTransformer implements TransformerInterface
{
    public function transform(mixed $value, array $config = []): mixed
    {
        return is_string($value) ? strtoupper($value) : $value;
    }
}
