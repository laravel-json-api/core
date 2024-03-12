<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Schema;

use LaravelJsonApi\Contracts\Pagination\Paginator;
use LaravelJsonApi\Contracts\Schema\Attribute;
use LaravelJsonApi\Contracts\Schema\ID;
use LaravelJsonApi\Contracts\Schema\Query as QueryContract;
use LaravelJsonApi\Contracts\Schema\Schema as SchemaContract;
use LaravelJsonApi\Contracts\Schema\Sortable;

class Query implements QueryContract
{
    /**
     * Query constructor
     *
     * @param SchemaContract $schema
     */
    public function __construct(private readonly SchemaContract $schema)
    {
    }

    /**
     * @inheritDoc
     */
    public function isFilter(string $name): bool
    {
        return $this->schema->isFilter($name);
    }

    /**
     * @inheritDoc
     */
    public function filters(): iterable
    {
        return $this->schema->filters();
    }

    /**
     * @inheritDoc
     */
    public function pagination(): ?Paginator
    {
        return $this->schema->pagination();
    }

    /**
     * @inheritDoc
     */
    public function includePaths(): iterable
    {
        return $this->schema->includePaths();
    }

    /**
     * @inheritDoc
     */
    public function isSparseField(string $fieldName): bool
    {
        return $this->schema->isSparseField($fieldName);
    }

    /**
     * @inheritDoc
     */
    public function sparseFields(): iterable
    {
        return $this->schema->sparseFields();
    }

    /**
     * @inheritDoc
     */
    public function isSortField(string $name): bool
    {
        return $this->schema->isSortField($name);
    }

    /**
     * @inheritDoc
     */
    public function sortFields(): iterable
    {
        return $this->schema->sortFields();
    }

    /**
     * @inheritDoc
     */
    public function sortField(string $name): ID|Attribute|Sortable
    {
        return $this->schema->sortField($name);
    }

    /**
     * @inheritDoc
     */
    public function sortables(): iterable
    {
        return $this->schema->sortables();
    }
}
