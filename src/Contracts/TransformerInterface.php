<?php

declare(strict_types=1);

namespace Mostafax\ErpIntegrationHub\Contracts;

interface TransformerInterface
{
    public function transform(mixed $value, array $config = []): mixed;
}
