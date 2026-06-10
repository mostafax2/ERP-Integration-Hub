<?php

declare(strict_types=1);

namespace Mostafax\ErpIntegrationHub\FieldMapping\Transformers;

use Mostafax\ErpIntegrationHub\Contracts\TransformerInterface;

class LookupTransformer implements TransformerInterface
{
    public function transform(mixed $value, array $config = []): mixed
    {
        $map      = $config['map'] ?? [];         // ['source_val' => 'dest_val']
        $default  = $config['default'] ?? $value;  // fallback if no match
        $strict   = $config['strict'] ?? false;    // if true, throw on miss

        $key = (string) $value;
        if (isset($map[$key])) {
            return $map[$key];
        }

        if ($strict) {
            throw new \InvalidArgumentException("Lookup transformer: no mapping found for value [{$value}]");
        }

        return $default;
    }
}
