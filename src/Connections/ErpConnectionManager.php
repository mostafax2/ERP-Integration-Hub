<?php

namespace Mostafax\ErpIntegrationHub\Connections;

use Mostafax\ErpIntegrationHub\Authentication\TokenManager;
use Mostafax\ErpIntegrationHub\Connections\Drivers\AbstractDynamicsDriver;
use Mostafax\ErpIntegrationHub\Contracts\ConnectionInterface;
use Mostafax\ErpIntegrationHub\Exceptions\ConnectionException;
use Mostafax\ErpIntegrationHub\Models\DynamicsConnection;

class ErpConnectionManager
{
    private array $drivers = [];
    private array $instances = [];

    public function __construct(private readonly TokenManager $tokenManager) {}

    public function registerDriver(string $name, string $driverClass): void
    {
        $this->drivers[$name] = $driverClass;
    }

    public function driver(DynamicsConnection $connection): ConnectionInterface
    {
        $key = $connection->id . ':' . $connection->driver;

        if (isset($this->instances[$key])) {
            return $this->instances[$key];
        }

        $driverClass = $this->resolveDriverClass($connection->driver);

        $this->instances[$key] = new $driverClass($connection, $this->tokenManager);
        return $this->instances[$key];
    }

    private function resolveDriverClass(string $driver): string
    {
        // Custom registered driver
        if (isset($this->drivers[$driver])) {
            return $this->drivers[$driver];
        }

        // Built-in drivers
        $builtIn = config("erp-integration-hub.drivers.{$driver}.driver");
        if ($builtIn && class_exists($builtIn)) {
            return $builtIn;
        }

        throw new ConnectionException("Unknown ERP driver: [{$driver}]. Register it via ErpIntegrationHub::extend().");
    }

    public function allDrivers(): array
    {
        $configured = array_keys(config('erp-integration-hub.drivers', []));
        $custom     = array_keys($this->drivers);
        return array_unique(array_merge($configured, $custom));
    }

    public function driverOptions(): array
    {
        $options = [];
        foreach (config('erp-integration-hub.drivers', []) as $key => $config) {
            $options[$key] = $config['label'] ?? $key;
        }
        foreach ($this->drivers as $key => $class) {
            $options[$key] = $options[$key] ?? $key;
        }
        return $options;
    }

    public function testConnection(DynamicsConnection $connection): array
    {
        try {
            $driver = $this->driver($connection);
            return $driver->test();
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
