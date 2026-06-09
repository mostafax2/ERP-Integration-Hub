<?php

namespace Mostafax\ErpIntegrationHub\Detection;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ModelDetector
{
    private array $detected = [];

    public function detect(): array
    {
        if (! empty($this->detected)) {
            return $this->detected;
        }

        $scanPaths = config('erp-integration-hub.detection.scan_paths', [app_path('Models')]);
        $exclude   = config('erp-integration-hub.detection.exclude_models', []);

        foreach ($scanPaths as $path) {
            if (! File::exists($path)) {
                continue;
            }
            foreach (File::allFiles($path) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }
                $class = $this->fileToClass($file->getRealPath());
                if ($class && $this->isEloquentModel($class)) {
                    $name = class_basename($class);
                    if (! in_array($name, $exclude)) {
                        $this->detected[$class] = $this->analyzeModel($class);
                    }
                }
            }
        }

        return $this->detected;
    }

    public function detectOne(string $class): ?array
    {
        if (! $this->isEloquentModel($class)) {
            return null;
        }
        return $this->analyzeModel($class);
    }

    private function analyzeModel(string $class): array
    {
        try {
            /** @var \Illuminate\Database\Eloquent\Model $instance */
            $instance = new $class();

            return [
                'class'        => $class,
                'name'         => class_basename($class),
                'table'        => $instance->getTable(),
                'fillable'     => $instance->getFillable(),
                'casts'        => $instance->getCasts(),
                'has_soft_deletes' => in_array(
                    \Illuminate\Database\Eloquent\SoftDeletes::class,
                    class_uses_recursive($class)
                ),
                'primary_key'  => $instance->getKeyName(),
                'columns'      => $this->getTableColumns($instance->getTable()),
                'relationships' => $this->detectRelationships($class),
            ];
        } catch (\Throwable) {
            return ['class' => $class, 'name' => class_basename($class), 'error' => 'Could not analyze'];
        }
    }

    private function getTableColumns(string $table): array
    {
        try {
            return \Illuminate\Support\Facades\Schema::getColumnListing($table);
        } catch (\Throwable) {
            return [];
        }
    }

    private function detectRelationships(string $class): array
    {
        $relations = [];
        $methods   = (new \ReflectionClass($class))->getMethods(\ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            if ($method->class !== $class || $method->getNumberOfParameters() > 0) {
                continue;
            }
            try {
                $returnType = $method->getReturnType();
                if (! $returnType) {
                    continue;
                }
                $typeName = $returnType instanceof \ReflectionNamedType ? $returnType->getName() : '';
                if (Str::startsWith($typeName, 'Illuminate\\Database\\Eloquent\\Relations\\')) {
                    $relations[] = [
                        'method' => $method->getName(),
                        'type'   => class_basename($typeName),
                    ];
                }
            } catch (\Throwable) {}
        }

        return $relations;
    }

    private function fileToClass(string $file): ?string
    {
        $content = file_get_contents($file);
        if (! preg_match('/^namespace\s+(.+?);/m', $content, $nsMatch)) {
            return null;
        }
        if (! preg_match('/^class\s+(\w+)/m', $content, $classMatch)) {
            return null;
        }
        return $nsMatch[1] . '\\' . $classMatch[1];
    }

    private function isEloquentModel(string $class): bool
    {
        try {
            if (! class_exists($class)) {
                return false;
            }
            $reflection = new \ReflectionClass($class);
            return ! $reflection->isAbstract()
                && $reflection->isSubclassOf(\Illuminate\Database\Eloquent\Model::class);
        } catch (\Throwable) {
            return false;
        }
    }
}
