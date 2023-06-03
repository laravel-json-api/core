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

namespace LaravelJsonApi\Core\Store;

use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Pagination\Page;
use LaravelJsonApi\Contracts\Query\QueryParameters;
use LaravelJsonApi\Contracts\Store\Builder;
use LaravelJsonApi\Contracts\Store\HasPagination;
use LaravelJsonApi\Contracts\Store\QueryManyBuilder;
use LogicException;
use function get_class;
use function sprintf;

class QueryManyHandler implements QueryManyBuilder, HasPagination
{

    /**
     * @var QueryManyBuilder
     */
    protected QueryManyBuilder $builder;

    /**
     * QueryAllHandler constructor.
     *
     * @param QueryManyBuilder $builder
     */
    public function __construct(QueryManyBuilder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * @inheritDoc
     */
    public function withRequest(?Request $request): Builder
    {
        $this->builder->withRequest($request);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function withQuery(QueryParameters $query): Builder
    {
        $this->builder->withQuery($query);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function with($includePaths): Builder
    {
        $this->builder->with($includePaths);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function filter(?array $filters): QueryManyBuilder
    {
        $this->builder->filter($filters);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function sort($fields): QueryManyBuilder
    {
        $this->builder->sort($fields);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function get(): iterable
    {
        return $this->builder->get();
    }

    /**
     * @inheritDoc
     */
    public function paginate(array $page): Page
    {
        if ($this->builder instanceof HasPagination) {
            return $this->builder->paginate($page);
        }

        throw new LogicException(sprintf(
            'Pagination is not supported by %s.',
            get_class($this->builder),
        ));
    }

    /**
     * @inheritDoc
     */
    public function getOrPaginate(?array $page): iterable
    {
        if ($this->builder instanceof HasPagination) {
            return $this->builder->getOrPaginate($page);
        }

        return $this->get();
    }

}
