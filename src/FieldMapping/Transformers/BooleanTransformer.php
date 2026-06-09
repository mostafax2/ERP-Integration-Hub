<?php

namespace Mostafax\ErpIntegrationHub\FieldMapping\Transformers;

use Mostafax\ErpIntegrationHub\Contracts\TransformerInterface;

class BooleanTransformer implements TransformerInterface
{
    public function transform(mixed $value, array $config = []): mixed
    {
        $trueValues  = $config['true_values']  ?? [1, '1', true, 'true', 'yes', 'on', 'active'];
        $falseValues = $config['false_values'] ?? [0, '0', false, 'false', 'no', 'off', 'inactive'];
        $format      = $config['format'] ?? 'bool'; // bool | int | string | yn

        $bool = in_array(strtolower((string) $value), array_map('strtolower', array_map('strval', $trueValues)));

        return match ($format) {
            'int'    => $bool ? 1 : 0,
            'string' => $bool ? 'true' : 'false',
            'yn'     => $bool ? 'Yes' : 'No',
            default  => $bool,
        };
    }
}
