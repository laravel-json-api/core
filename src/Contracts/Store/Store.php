<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

namespace LaravelJsonApi\Contracts\Store;

use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;

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
     * @return QueryOneBuilder
     */
    public function queryOne(ResourceType|string $type, ResourceId|string $id): QueryOneBuilder;

    /**
     * Query a to-one relationship.
     *
     * @param ResourceType|string $type
     * @param ResourceId|string $id
     * @param string $fieldName
     * @return QueryOneBuilder
     */
    public function queryToOne(ResourceType|string $type, ResourceId|string $id, string $fieldName): QueryOneBuilder;

    /**
     * Query a to-many relationship.
     *
     * @param ResourceType|string $type
     * @param ResourceId|string $id
     * @param string $fieldName
     * @return QueryManyBuilder&HasPagination
     */
    public function queryToMany(
        ResourceType|string $type,
        ResourceId|string $id,
        string $fieldName
    ): QueryManyBuilder&HasPagination;

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
     * @param ResourceType|string $resourceType
     * @param object|string $modelOrResourceId
     * @return ResourceBuilder
     */
    public function update(ResourceType|string $resourceType, $modelOrResourceId): ResourceBuilder;

    /**
     * Delete an existing resource.
     *
     * @param ResourceType|string $resourceType
     * @param object|string $modelOrResourceId
     * @return void
     */
    public function delete(ResourceType|string $resourceType, $modelOrResourceId): void;

    /**
     * Modify a to-one relation.
     *
     * @param ResourceType|string $resourceType
     * @param object|string $modelOrResourceId
     * @param string $fieldName
     * @return ToOneBuilder
     */
    public function modifyToOne(
        ResourceType|string $resourceType,
        $modelOrResourceId,
        string $fieldName,
    ): ToOneBuilder;

    /**
     * Modify a to-many relation.
     *
     * @param ResourceType|string $resourceType
     * @param object|string $modelOrResourceId
     * @param string $fieldName
     * @return ToManyBuilder
     */
    public function modifyToMany(
        ResourceType|string $resourceType,
        $modelOrResourceId,
        string $fieldName,
    ): ToManyBuilder;

    /**
     * Access a resource repository by its JSON:API resource type.
     *
     * @param ResourceType|string $resourceType
     * @return Repository|null
     */
    public function resources(ResourceType|string $resourceType): ?Repository;
}
