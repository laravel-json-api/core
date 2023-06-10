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

namespace LaravelJsonApi\Core\Bus\Queries\Concerns;

use LaravelJsonApi\Core\Document\Input\Values\ResourceId;
use LaravelJsonApi\Core\Store\ModelKey;
use RuntimeException;

trait Identifiable
{
    /**
     * @var ResourceId|null
     */
    private ?ResourceId $id = null;

    /**
     * @var object|null
     */
    private ?object $model = null;

    /**
     * @var ModelKey|null
     */
    private ?ModelKey $modelKey = null;

    /**
     * @return ResourceId|null
     */
    public function id(): ?ResourceId
    {
        return $this->id;
    }

    /**
     * @return ResourceId|ModelKey
     */
    public function idOrKey(): ResourceId|ModelKey
    {
        if ($this->id !== null) {
            return $this->id;
        }

        if ($this->modelKey !== null) {
            return $this->modelKey;
        }

        throw new RuntimeException('Expecting a resource id or model key to be set on the query.');
    }

    /**
     * Return a new instance with the resource id set, if the value is not null.
     *
     * @param ResourceId|string|null $id
     * @return $this
     */
    public function maybeWithId(ResourceId|string|null $id): static
    {
        if ($id !== null) {
            return $this->withId($id);
        }

        return $this;
    }

    /**
     * Return a new instance with the resource id set.
     *
     * @param ResourceId|string $id
     * @return static
     */
    public function withId(ResourceId|string $id): static
    {
        if ($this->id === null) {
            $copy = clone $this;
            $copy->id = ResourceId::cast($id);
            return $copy;
        }

        throw new RuntimeException('Resource id is already set on query.');
    }


    /**
     * Set the model for the query, if known.
     *
     * @param object|null $model
     * @return static
     */
    public function withModel(?object $model): static
    {
        $copy = clone $this;
        $copy->model = $model;

        return $copy;
    }

    /**
     * Get the model for the query.
     *
     * @return object|null
     */
    public function model(): ?object
    {
        return $this->model;
    }

    /**
     * Get the model for the query.
     *
     * @return object
     */
    public function modelOrFail(): object
    {
        if ($this->model !== null) {
            return $this->model;
        }

        throw new RuntimeException('Expecting a model to be set on the query.');
    }

    /**
     * Return a new instance with the model key set.
     *
     * @param ModelKey|string|int|null $key
     * @return static
     */
    public function withModelKey(ModelKey|string|int|null $key): static
    {
        $copy = clone $this;
        $copy->modelKey = ModelKey::nullable($key);

        return $copy;
    }

    /**
     * @return ModelKey|null
     */
    public function modelKey(): ?ModelKey
    {
        return $this->modelKey;
    }
}
