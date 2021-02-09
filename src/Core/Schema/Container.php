<?php
/*
 * Copyright 2021 Cloud Creativity Limited
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

use Illuminate\Contracts\Container\Container as IlluminateContainer;
use LaravelJsonApi\Contracts\Schema\Container as ContainerContract;
use LaravelJsonApi\Contracts\Schema\Schema;
use LogicException;
use RuntimeException;
use Throwable;
use function collect;
use function get_class;
use function is_object;
use function is_string;

class Container implements ContainerContract
{

    /**
     * @var IlluminateContainer
     */
    private IlluminateContainer $container;

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
     * Container constructor.
     *
     * @param IlluminateContainer $container
     * @param iterable $schemas
     */
    public function __construct(IlluminateContainer $container, iterable $schemas)
    {
        $this->container = $container;
        $this->types = [];
        $this->models = [];
        $this->schemas = [];

        foreach ($schemas as $schemaClass) {
            $this->types[$schemaClass::type()] = $schemaClass;
            $this->models[$schemaClass::model()] = $schemaClass;
        }

        ksort($this->types);
    }

    /**
     * @inheritDoc
     */
    public function schemaFor(string $resourceType): Schema
    {
        if (isset($this->types[$resourceType])) {
            return $this->resolve($this->types[$resourceType]);
        }

        throw new LogicException("No schema for JSON:API resource type {$resourceType}.");
    }

    /**
     * @inheritDoc
     */
    public function schemaForModel($model): Schema
    {
        $model = is_object($model) ? get_class($model) : $model;

        if (is_string($model) && isset($this->models[$model])) {
            return $this->resolve($this->models[$model]);
        }

        throw new LogicException("No JSON:API schema for model {$model}.");
    }

    /**
     * @inheritDoc
     */
    public function exists(string $resourceType): bool
    {
        return isset($this->types[$resourceType]);
    }

    /**
     * @inheritDoc
     */
    public function types(): array
    {
        return array_keys($this->types);
    }

    /**
     * @inheritDoc
     */
    public function resources(): array
    {
        return collect($this->models)
            ->map(fn($schemaClass) => $schemaClass::resource())
            ->all();
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
            $schema = $this->container->make($schemaClass, ['schemas' => $this]);
        } catch (Throwable $ex) {
            throw new RuntimeException("Unable to create schema {$schemaClass}.", 0, $ex);
        }

        if ($schema instanceof Schema) {
            return $schema;
        }

        throw new RuntimeException("Class {$schemaClass} is not a JSON API schema.");
    }
}
