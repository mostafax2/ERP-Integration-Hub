<?php

namespace Mostafax\ErpIntegrationHub\FieldMapping\Transformers;

use Mostafax\ErpIntegrationHub\Contracts\TransformerInterface;

class SplitTransformer implements TransformerInterface
{
    public function transform(mixed $value, array $config = []): mixed
    {
        $delimiter = $config['delimiter'] ?? ',';
        $index     = $config['index'] ?? null; // null = return all parts as array
        $parts     = explode($delimiter, (string) $value);
        return $index !== null ? (trim($parts[$index] ?? '') ?: null) : array_map('trim', $parts);
    }
}
