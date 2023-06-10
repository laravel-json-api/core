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

namespace LaravelJsonApi\Core\Bus\Queries;

use LaravelJsonApi\Core\Document\Input\Values\ResourceId;
use LaravelJsonApi\Core\Store\ModelKey;

interface IsIdentifiable
{
    /**
     * @return ResourceId|null
     */
    public function id(): ?ResourceId;

    /**
     * Return a new instance with the resource id set.
     *
     * @param ResourceId|string $id
     * @return static
     */
    public function withId(ResourceId|string $id): static;

    /**
     * Get the model for the query, if there is one.
     *
     * @return object|null
     */
    public function model(): ?object;

    /**
     * Get the model for the query, or fail if there isn't one.
     *
     * @return object
     */
    public function modelOrFail(): object;

    /**
     * Return a new instance with the model set.
     *
     * @param object|null $model
     * @return static
     */
    public function withModel(?object $model): static;

    /**
     * @return ModelKey|null
     */
    public function modelKey(): ?ModelKey;
}
