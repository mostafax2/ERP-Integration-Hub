<?php

declare(strict_types=1);

namespace Mostafax\ErpIntegrationHub\FieldMapping\Transformers;

use Mostafax\ErpIntegrationHub\Contracts\TransformerInterface;

class NumberTransformer implements TransformerInterface
{
    public function transform(mixed $value, array $config = []): mixed
    {
        $decimals = (int) ($config['decimals'] ?? 2);
        $decimal  = $config['decimal_separator'] ?? '.';
        $thousands = $config['thousands_separator'] ?? '';
        $cast = $config['cast'] ?? 'float'; // float | int | string

        $num = (float) str_replace([',', ' '], ['', ''], (string) $value);

        return match ($cast) {
            'int'    => (int) $num,
            'string' => number_format($num, $decimals, $decimal, $thousands),
            default  => round($num, $decimals),
        };
    }
}
