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

namespace LaravelJsonApi\Core\Resources;

use InvalidArgumentException;
use LaravelJsonApi\Contracts\Resources\Factory as FactoryContract;
use LaravelJsonApi\Contracts\Schema\Container as SchemaContainer;
use LaravelJsonApi\Contracts\Schema\Schema;
use LogicException;
use Throwable;
use function array_keys;
use function class_exists;
use function get_class;
use function sprintf;

class DefaultFactory implements FactoryContract
{

    /**
     * @var SchemaContainer
     */
    private SchemaContainer $schemas;

    /**
     * @var string
     */
    private string $class;

    /**
     * @var array
     */
    private array $bindings;

    /**
     * Construct a new default factory.
     *
     * @param SchemaContainer $schemas
     * @return static
     */
    public static function make(SchemaContainer $schemas): self
    {
        return new self($schemas, ResourceResolver::defaultResource());
    }

    /**
     * DefaultFactory constructor.
     *
     * @param SchemaContainer $schemas
     * @param string $class
     */
    public function __construct(SchemaContainer $schemas, string $class)
    {
        if (!class_exists($class)) {
            throw new InvalidArgumentException("Expecting resource class {$class} to exist.");
        }

        $this->schemas = $schemas;
        $this->class = $class;
        $this->bindings = collect($schemas->resources())
            ->filter(static fn($resourceClass) => $class === $resourceClass)
            ->all();
    }

    /**
     * @inheritDoc
     */
    public function handles(): iterable
    {
        return array_keys($this->bindings);
    }

    /**
     * @inheritDoc
     */
    public function createResource(object $model): JsonApiResource
    {
        $exists = $this->bindings[get_class($model)] ?? false;

        if (false === $exists) {
            throw new LogicException(sprintf(
                'Unexpected model class - %s',
                get_class($model)
            ));
        }

        try {
            return $this->build(
                $this->schemas->schemaForModel($model),
                $model
            );
        } catch (Throwable $ex) {
            throw new LogicException(sprintf(
                'Failed to build a default resource object for model %s.',
                get_class($model),
            ), 0, $ex);
        }
    }

    /**
     * Build a new resource object instance.
     *
     * @param Schema $schema
     * @param object $model
     * @return JsonApiResource
     */
    protected function build(Schema $schema, object $model): JsonApiResource
    {
        $fqn = $this->class;

        return new $fqn($schema, $model);
    }

}
