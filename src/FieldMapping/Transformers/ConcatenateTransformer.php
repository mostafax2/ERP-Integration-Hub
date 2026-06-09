<?php

namespace Mostafax\ErpIntegrationHub\FieldMapping\Transformers;

use Mostafax\ErpIntegrationHub\Contracts\TransformerInterface;

class ConcatenateTransformer implements TransformerInterface
{
    public function transform(mixed $value, array $config = []): mixed
    {
        $parts     = (array) $value;
        $separator = $config['separator'] ?? ' ';
        $prefix    = $config['prefix']    ?? '';
        $suffix    = $config['suffix']    ?? '';
        return $prefix . implode($separator, array_filter($parts, fn($p) => $p !== null && $p !== '')) . $suffix;
    }
}
