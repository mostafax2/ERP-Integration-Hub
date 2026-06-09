<?php

namespace Mostafax\ErpIntegrationHub\FieldMapping\Transformers;

use Mostafax\ErpIntegrationHub\Contracts\TransformerInterface;

class TrimTransformer implements TransformerInterface
{
    public function transform(mixed $value, array $config = []): mixed
    {
        return is_string($value) ? trim($value, $config['chars'] ?? " \t\n\r\0\x0B") : $value;
    }
}
