<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Contracts\Resources;

use Generator;
use LaravelJsonApi\Core\Resources\JsonApiResource;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;

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
     * Create a resource object for the supplied model.
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

    /**
     * Get the resource id for the supplied model.
     *
     * @param object $model
     * @return ResourceId
     */
    public function idFor(object $model): ResourceId;

    /**
     * Get the resource id for the provided model of the expected type.
     *
     * @param ResourceType $expected
     * @param object $model
     * @return ResourceId
     */
    public function idForType(ResourceType $expected, object $model): ResourceId;
}
