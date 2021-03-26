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

namespace LaravelJsonApi\Contracts\Store;

use LaravelJsonApi\Contracts\Pagination\Page;
use LaravelJsonApi\Core\Query\SortField;
use LaravelJsonApi\Core\Query\SortFields;

interface QueryManyBuilder extends Builder
{

    /**
     * Filter models using JSON:API filter parameters.
     *
     * @param array|null $filters
     * @return $this
     */
    public function filter(?array $filters): self;

    /**
     * Sort models using JSON:API sort fields.
     *
     * @param SortFields|SortField|array|string|null $fields
     * @return $this
     */
    public function sort($fields): self;

    /**
     * Get all the results of the query.
     *
     * The method MAY return a page, if it is not possible to retrieve all
     * resources in a single query.
     *
     * @return iterable|Page
     */
    public function get(): iterable;

}
