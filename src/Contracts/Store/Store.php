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

namespace LaravelJsonApi\Contracts\Store;

use LaravelJsonApi\Core\Document\Input\Values\ResourceId;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;

interface Store
{
    /**
     * Get a model by JSON:API resource type and id.
     *
     * @param ResourceType|string $resourceType
     * @param ResourceId|string $resourceId
     * @return object|null
     */
    public function find(ResourceType|string $resourceType, ResourceId|string $resourceId): ?object;

    /**
     * Find the supplied model or throw a runtime exception if it does not exist.
     *
     * @param string $resourceType
     * @param string $resourceId
     * @return object
     */
    public function findOrFail(string $resourceType, string $resourceId): object;

    /**
     * @param array $identifiers
     * @return iterable
     */
    public function findMany(array $identifiers): iterable;

    /**
     * Does a model exist for the supplied resource type and id?
     *
     * @param string $resourceType
     * @param string $resourceId
     * @return bool
     */
    public function exists(string $resourceType, string $resourceId): bool;

    /**
     * Query all resources by JSON:API resource type.
     *
     * @param ResourceType|string $type
     * @return QueryManyBuilder&HasPagination&HasSingularFilters
     */
    public function queryAll(ResourceType|string $type): QueryManyBuilder&HasPagination&HasSingularFilters;

    /**
     * Query one resource by JSON:API resource type.
     *
     * @param ResourceType|string $type
     * @param ResourceId|string $id
     *      string is interpreted as the resource id, not a model key.
     * @return QueryOneBuilder
     */
    public function queryOne(ResourceType|string $type, ResourceId|string $id): QueryOneBuilder;

    /**
     * Query a to-one relationship.
     *
     * @param string $resourceType
     * @param object|string $modelOrResourceId
     * @param string $fieldName
     * @return QueryOneBuilder
     */
    public function queryToOne(string $resourceType, $modelOrResourceId, string $fieldName): QueryOneBuilder;

    /**
     * Query a to-many relationship.
     *
     * @param string $resourceType
     * @param object|string $modelOrResourceId
     * @param string $fieldName
     * @return QueryManyBuilder|HasPagination
     */
    public function queryToMany(string $resourceType, $modelOrResourceId, string $fieldName): QueryManyBuilder;

    /**
     * Create a new resource.
     *
     * @param ResourceType|string $resourceType
     * @return ResourceBuilder
     */
    public function create(ResourceType|string $resourceType): ResourceBuilder;

    /**
     * Update an existing resource.
     *
     * @param string $resourceType
     * @param object|string $modelOrResourceId
     * @return ResourceBuilder
     */
    public function update(string $resourceType, $modelOrResourceId): ResourceBuilder;

    /**
     * Delete an existing resource.
     *
     * @param string $resourceType
     * @param object|string $modelOrResourceId
     * @return void
     */
    public function delete(string $resourceType, $modelOrResourceId): void;

    /**
     * Modify a to-one relation.
     *
     * @param string $resourceType
     * @param object|string $modelOrResourceId
     * @param string $fieldName
     * @return ToOneBuilder
     */
    public function modifyToOne(string $resourceType, $modelOrResourceId, string $fieldName): ToOneBuilder;

    /**
     * Modify a to-many relation.
     *
     * @param string $resourceType
     * @param object|string $modelOrResourceId
     * @param string $fieldName
     * @return ToManyBuilder
     */
    public function modifyToMany(string $resourceType, $modelOrResourceId, string $fieldName): ToManyBuilder;

    /**
     * Access a resource repository by its JSON:API resource type.
     *
     * @param ResourceType|string $resourceType
     * @return Repository|null
     */
    public function resources(ResourceType|string $resourceType): ?Repository;
}
