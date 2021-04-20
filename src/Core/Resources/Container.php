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

use Generator;
use LaravelJsonApi\Contracts\Resources\Container as ContainerContract;
use LaravelJsonApi\Contracts\Resources\Factory;
use LogicException;
use function get_class;
use function is_iterable;
use function is_object;
use function sprintf;

class Container implements ContainerContract
{

    /**
     * @var Factory
     */
    private Factory $factory;

    /**
     * Container constructor.
     *
     * @param Factory $factory
     */
    public function __construct(Factory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @inheritDoc
     */
    public function resolve($value)
    {
        if ($value instanceof JsonApiResource) {
            return $value;
        }

        if (is_object($value) && $this->exists($value)) {
            return $this->create($value);
        }

        if (is_iterable($value)) {
            return $this->cursor($value);
        }

        throw new LogicException(sprintf(
            'Unable to resolve %s to a resource object. Check your resource configuration.',
            is_object($value) ? get_class($value) : 'non-object value'
        ));
    }

    /**
     * @inheritDoc
     */
    public function exists(object $model): bool
    {
        return $this->factory->canCreate($model);
    }

    /**
     * @inheritDoc
     */
    public function create(object $model): JsonApiResource
    {
        return $this->factory->createResource(
            $model
        );
    }

    /**
     * @inheritDoc
     */
    public function cast(object $modelOrResource): JsonApiResource
    {
        if ($modelOrResource instanceof JsonApiResource) {
            return $modelOrResource;
        }

        return $this->create($modelOrResource);
    }

    /**
     * @inheritDoc
     */
    public function cursor(iterable $models): Generator
    {
        foreach ($models as $model) {
            if ($model instanceof JsonApiResource) {
                yield $model;
                continue;
            }

            yield $this->create($model);
        }
    }

}
