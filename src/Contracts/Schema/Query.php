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

use LaravelJsonApi\Contracts\Pagination\Paginator;

interface Query
{
    /**
     * Is the provided name a filter parameter?
     *
     * @param string $name
     * @return bool
     */
    public function isFilter(string $name): bool;

    /**
     * Get the filters for the resource.
     *
     * @return iterable<Filter>
     */
    public function filters(): iterable;

    /**
     * Get the paginator to use when fetching collections of this resource.
     *
     * @return Paginator|null
     */
    public function pagination(): ?Paginator;

    /**
     * Get the include paths supported by this resource.
     *
     * @return iterable<string>
     */
    public function includePaths(): iterable;

    /**
     * Is the provided field name a sparse field?
     *
     * @param string $fieldName
     * @return bool
     */
    public function isSparseField(string $fieldName): bool;

    /**
     * Get the sparse fields that are supported by this resource.
     *
     * @return iterable<string>
     */
    public function sparseFields(): iterable;

    /**
     * Is the provided name a sort field?
     *
     * @param string $name
     * @return bool
     */
    public function isSortField(string $name): bool;

    /**
     * Get the parameter names that can be used to sort this resource.
     *
     * @return iterable<string>
     */
    public function sortFields(): iterable;

    /**
     * Get a sort field by name.
     *
     * @param string $name
     * @return ID|Attribute|Sortable
     */
    public function sortField(string $name): ID|Attribute|Sortable;

    /**
     * Get additional sortables.
     *
     * Get sortables that are not the resource ID or a resource attribute.
     *
     * @return iterable<Sortable>
     */
    public function sortables(): iterable;
}
