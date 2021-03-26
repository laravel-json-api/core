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

namespace LaravelJsonApi\Contracts\Resources;

use Generator;
use LaravelJsonApi\Core\Resources\JsonApiResource;

interface Container
{

    /**
     * Resolve the value to a resource object or a cursor of resource objects.
     *
     * @param mixed $value
     *      a resource object, model or an iterable of models.
     * @return JsonApiResource|Generator
     */
    public function resolve($value);

    /**
     * Can the provided model be converted to a resource object?
     *
     * @param object $model
     * @return bool
     */
    public function exists(object $model): bool;

    /**
     * Create a resource object for the supplied models.
     *
     * @param object $model
     * @return JsonApiResource
     */
    public function create(object $model): JsonApiResource;

    /**
     * Cast the value to a JSON:API resource.
     *
     * @param JsonApiResource|object $modelOrResource
     * @return JsonApiResource
     */
    public function cast(object $modelOrResource): JsonApiResource;

    /**
     * Get a cursor that converts the provided models to resource objects.
     *
     * @param iterable $models
     * @return Generator
     */
    public function cursor(iterable $models): Generator;
}
