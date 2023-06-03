<?php
/*
 * Copyright 2023 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Schema;

use LaravelJsonApi\Contracts\Schema\Container as ContainerContract;
use LaravelJsonApi\Contracts\Schema\Schema;
use LaravelJsonApi\Contracts\Server\Server;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;
use LaravelJsonApi\Core\Support\ContainerResolver;
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
        $this->models = [];
        $this->schemas = [];
        $this->aliases = [];

        foreach ($schemas as $schemaClass) {
            $this->types[$schemaClass::type()] = $schemaClass;
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
        $resourceType = (string) $resourceType;

        if (isset($this->types[$resourceType])) {
            return $this->resolve($this->types[$resourceType]);
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
