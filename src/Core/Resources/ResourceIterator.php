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
use IteratorAggregate;
use LaravelJsonApi\Contracts\Resources\Container as ContainerContract;

class ResourceIterator implements IteratorAggregate
{

    /**
     * @var ContainerContract
     */
    private ContainerContract $container;

    /**
     * @var iterable
     */
    private iterable $models;

    /**
     * For generators, the cached models.
     *
     * @var array|null
     */
    private ?array $cache = null;

    /**
     * ResourceIterator constructor.
     *
     * @param ContainerContract $container
     * @param iterable $models
     */
    public function __construct(ContainerContract $container, iterable $models)
    {
        $this->container = $container;
        $this->models = $models;
    }

    /**
     * @return Generator
     */
    public function cursor(): Generator
    {
        if (null !== $this->cache) {
            yield from $this->cache;
            return;
        }

        $this->cache = [];

        foreach ($this->container->cursor($this->models) as $resource) {
            yield $this->cache[] = $resource;
        }
    }

    /**
     * @inheritDoc
     */
    public function getIterator()
    {
        yield from $this->cursor();
    }

}
