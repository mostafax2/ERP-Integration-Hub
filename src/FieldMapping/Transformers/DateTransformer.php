<?php

namespace Mostafax\ErpIntegrationHub\FieldMapping\Transformers;

use Mostafax\ErpIntegrationHub\Contracts\TransformerInterface;

class DateTransformer implements TransformerInterface
{
    public function transform(mixed $value, array $config = []): mixed
    {
        if (empty($value)) {
            return $value;
        }
        $format = $config['format'] ?? 'Y-m-d';
        try {
            $dt = new \DateTime((string) $value);
            return $dt->format($format);
        } catch (\Throwable) {
            return $value;
        }
    }
}
