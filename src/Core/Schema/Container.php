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
use LaravelJsonApi\Contracts\Server\Server;
use LaravelJsonApi\Core\Support\ContainerResolver;
use LaravelJsonApi\Core\Values\ResourceType;
use LogicException;
use RuntimeException;
use Throwable;
use function get_class;
use function is_object;

class Container implements ContainerContract
{
    /**
     * @var ContainerResolver
     */
    private ContainerResolver $container;

    /**
     * @var Server
     */
    private Server $server;

    /**
     * @var array
     */
    private array $types;

    /**
     * @var array
     */
    private array $uriTypes;

    /**
     * @var array
     */
    private array $models;

    /**
     * @var array
     */
    private array $schemas;

    /**
     * @var array
     */
    private array $aliases;

    /**
     * Container constructor.
     *
     * @param ContainerResolver $container
     * @param Server $server
     * @param iterable $schemas
     */
    public function __construct(ContainerResolver $container, Server $server, iterable $schemas)
    {
        $this->container = $container;
        $this->server = $server;
        $this->types = [];
        $this->uriTypes = [];
        $this->models = [];
        $this->schemas = [];
        $this->aliases = [];

        foreach ($schemas as $schemaClass) {
            $type = $schemaClass::type();
            $this->types[$type] = $schemaClass;
            $this->uriTypes[$schemaClass::uriType()] = $type;
            $this->models[$schemaClass::model()] = $schemaClass;
        }

        ksort($this->types);
    }

    /**
     * @inheritDoc
     */
    public function exists(string|ResourceType $resourceType): bool
    {
        $resourceType = (string) $resourceType;

        return isset($this->types[$resourceType]);
    }

    /**
     * @inheritDoc
     */
    public function schemaFor(string|ResourceType $resourceType): Schema
    {
        return $this->resolve(
            $this->schemaClassFor($resourceType),
        );
    }

    /**
     * @inheritDoc
     */
    public function schemaClassFor(string|ResourceType $type): string
    {
        $type = (string) $type;

        if (isset($this->types[$type])) {
            return $this->types[$type];
        }

        throw new LogicException("No schema for JSON:API resource type {$resourceType}.");
    }

    /**
     * @inheritDoc
     */
    public function modelClassFor(string|ResourceType $resourceType): string
    {
        return $this
            ->schemaFor($resourceType)
            ->model();
    }

    /**
     * @inheritDoc
     */
    public function existsForModel($model): bool
    {
        return !empty($this->resolveModelClassFor($model));
    }

    /**
     * @inheritDoc
     */
    public function schemaForModel($model): Schema
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
        $value = $this->uriTypes[$uriType] ?? null;

        return $value ? new ResourceType($value) : null;
    }

    /**
     * @inheritDoc
     */
    public function types(): array
    {
        return array_keys($this->types);
    }

    /**
     * Resolve the JSON:API model class for the provided object.
     *
     * @param string|object $model
     * @return string|null
     */
    private function resolveModelClassFor(string|object $model): ?string
    {
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
