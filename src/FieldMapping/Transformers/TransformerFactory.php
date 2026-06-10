<?php

declare(strict_types=1);

namespace Mostafax\ErpIntegrationHub\FieldMapping\Transformers;

use Mostafax\ErpIntegrationHub\Contracts\TransformerInterface;

class TransformerFactory
{
    private static array $map = [
        'uppercase'    => UppercaseTransformer::class,
        'lowercase'    => LowercaseTransformer::class,
        'trim'         => TrimTransformer::class,
        'date_format'  => DateTransformer::class,
        'number_format' => NumberTransformer::class,
        'boolean'      => BooleanTransformer::class,
        'concatenate'  => ConcatenateTransformer::class,
        'split'        => SplitTransformer::class,
        'lookup'       => LookupTransformer::class,
    ];

    public static function make(string $type): TransformerInterface
    {
        if (! isset(self::$map[$type])) {
            throw new \InvalidArgumentException("Unknown transformer type: [{$type}]");
        }
        return new (self::$map[$type])();
    }

    public static function register(string $type, string $class): void
    {
        self::$map[$type] = $class;
    }

    public static function all(): array
    {
        return array_keys(self::$map);
    }
}
