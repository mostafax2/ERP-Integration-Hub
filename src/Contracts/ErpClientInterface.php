<?php

namespace Mostafax\ErpIntegrationHub\Contracts;

interface ErpClientInterface
{
    public function get(string $endpoint, array $params = []): array;

    public function post(string $endpoint, array $data): array;

    public function patch(string $endpoint, array $data): array;

    public function delete(string $endpoint): bool;

    public function getToken(): string;

    public function refreshToken(): string;

    public function setCompany(string $company): static;

    public function paginate(string $endpoint, array $params = [], int $pageSize = 100): \Generator;
}
