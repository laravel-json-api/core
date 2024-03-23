<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Schema;

use LaravelJsonApi\Contracts\Schema\Container as ContainerContract;
use LaravelJsonApi\Contracts\Schema\Schema;
use LaravelJsonApi\Contracts\Schema\StaticSchema\StaticContainer;
use LaravelJsonApi\Contracts\Server\Server;
use LaravelJsonApi\Core\Support\ContainerResolver;
use LaravelJsonApi\Core\Values\ResourceType;
use LogicException;
use RuntimeException;
use Throwable;
use function get_class;
use function is_object;

final class Container implements ContainerContract
{
    /**
     * @var array
     */
    private array $schemas;

    /**
     * @var array
     */
    private array $aliases;

    /**
     * @var array<class-string, class-string<Schema>>|null
     */
    private ?array $models = null;

    /**
     * Container constructor.
     *
     * @param ContainerResolver $container
     * @param Server $server
     * @param StaticContainer $staticSchemas
     */
    public function __construct(
        private readonly ContainerResolver $container,
        private readonly Server $server,
        private readonly StaticContainer $staticSchemas,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function exists(string|ResourceType $resourceType): bool
    {
        return $this->staticSchemas->exists($resourceType);
    }

    /**
     * @inheritDoc
     */
    public function schemaFor(string|ResourceType $resourceType): Schema
    {
        $class = $this->staticSchemas->schemaClassFor($resourceType);

        return $this->resolve($class);
    }

    /**
     * @inheritDoc
     */
    public function schemaClassFor(string|ResourceType $type): string
    {
        return $this->staticSchemas->schemaClassFor($type);
    }

    /**
     * @inheritDoc
     */
    public function modelClassFor(string|ResourceType $resourceType): string
    {
        return $this->staticSchemas->modelClassFor($resourceType);
    }

    /**
     * @inheritDoc
     */
    public function existsForModel(string|object $model): bool
    {
        return !empty($this->resolveModelClassFor($model));
    }

    /**
     * @inheritDoc
     */
    public function schemaForModel(string|object $model): Schema
    {
        if ($class = $this->resolveModelClassFor($model)) {
            return $this->resolve(
                $this->models[$class]
            );
        }

        throw new LogicException(sprintf(
            'No JSON:API schema for model %s.',
            is_object($model) ? get_class($model) : $model,
        ));
    }

    /**
     * @inheritDoc
     */
    public function schemaTypeForUri(string $uriType): ?ResourceType
    {
        return $this->staticSchemas->typeForUri($uriType);
    }

    /**
     * @inheritDoc
     */
    public function types(): array
    {
        return $this->staticSchemas->types();
    }

    /**
     * Resolve the JSON:API model class for the provided object.
     *
     * @param string|object $model
     * @return string|null
     */
    private function resolveModelClassFor(string|object $model): ?string
    {
        if ($this->models === null) {
            $this->models = [];
            foreach ($this->staticSchemas as $staticSchema) {
                $this->models[$staticSchema->getModel()] = $staticSchema->getSchemaClass();
            }
        }

        $model = is_object($model) ? get_class($model) : $model;
        $model = $this->aliases[$model] ?? $model;

        if (isset($this->models[$model])) {
            return $model;
        }

        foreach (class_parents($model) as $parent) {
            if (isset($this->models[$parent])) {
                return $this->aliases[$model] = $parent;
            }
        }

        foreach (class_implements($model) as $interface) {
            if (isset($this->models[$interface])) {
                return $this->aliases[$model] = $interface;
            }
        }

        return null;
    }

    /**
     * @param string $schemaClass
     * @return Schema
     */
    private function resolve(string $schemaClass): Schema
    {
        if (isset($this->schemas[$schemaClass])) {
            return $this->schemas[$schemaClass];
        }

        return $this->schemas[$schemaClass] = $this->make($schemaClass);
    }

    /**
     * @param string $schemaClass
     * @return Schema
     */
    private function make(string $schemaClass): Schema
    {
        try {
            $schema = $this->container->instance()->make($schemaClass, [
                'schemas' => $this,
                'server' => $this->server,
                'static' => $this->staticSchemas->schemaFor($schemaClass),
            ]);
        } catch (Throwable $ex) {
            throw new RuntimeException("Unable to create schema {$schemaClass}.", 0, $ex);
        }

        if ($schema instanceof Schema) {
            return $schema;
        }

        throw new RuntimeException("Class {$schemaClass} is not a JSON:API schema.");
    }
}
