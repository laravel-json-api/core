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
     * @param string|object $model
     * @return Schema
     */
    public function schemaForModel($model): Schema;

    /**
     * Does a schema exist for the provided model class?
     *
     * @param string|object $model
     * @return bool
     */
    public function existsForModel($model): bool;

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
