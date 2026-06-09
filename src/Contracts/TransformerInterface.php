<?php

namespace Mostafax\ErpIntegrationHub\Contracts;

interface TransformerInterface
{
    public function transform(mixed $value, array $config = []): mixed;
}
