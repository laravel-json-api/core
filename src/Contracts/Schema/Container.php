<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Contracts\Schema;

use LaravelJsonApi\Core\Values\ResourceType;

interface Container
{
    /**
     * Does a schema exist for the supplied resource type?
     *
     * @param string|ResourceType $resourceType
     * @return bool
     */
    public function exists(string|ResourceType $resourceType): bool;

    /**
     * Get a schema by JSON:API resource type.
     *
     * @param string|ResourceType $resourceType
     * @return Schema
     */
    public function schemaFor(string|ResourceType $resourceType): Schema;

    /**
     * Get the schema class for a JSON:API resource type.
     *
     * @param ResourceType|string $type
     * @return string
     */
    public function schemaClassFor(ResourceType|string $type): string;

    /**
     * Get a schema for the provided model class.
     *
     * @param class-string|object $model
     * @return Schema
     */
    public function schemaForModel(string|object $model): Schema;

    /**
     * Does a schema exist for the provided model class?
     *
     * @param class-string|object $model
     * @return bool
     */
    public function existsForModel(string|object $model): bool;

    /**
     * Get the fully qualified model class for the provided resource type.
     *
     * @param string|ResourceType $resourceType
     * @return string
     */
    public function modelClassFor(string|ResourceType $resourceType): string;

    /**
     * Get the schema resource type for the provided type as it appears in URLs.
     *
     * @param string $uriType
     * @return ResourceType|null
     */
    public function schemaTypeForUri(string $uriType): ?ResourceType;

    /**
     * Get a list of all the supported resource types.
     *
     * @return array
     */
    public function types(): array;

}
