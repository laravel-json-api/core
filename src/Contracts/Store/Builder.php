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

namespace LaravelJsonApi\Contracts\Store;

use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Query\QueryParameters;
use LaravelJsonApi\Core\Query\IncludePaths;
use LaravelJsonApi\Core\Query\RelationshipPath;

interface Builder
{

    /**
     * Use the request as the context for the building action.
     *
     * When setting the request, the request query parameters MUST be used.
     *
     * @param Request|null $request
     * @return $this
     */
    public function withRequest(?Request $request): self;

    /**
     * Use the provided query parameters for the building action.
     *
     * Query parameters can be specified to either override the query parameters
     * from the request context, or to execute the building action outside of a
     * HTTP request.
     *
     * @param QueryParameters $query
     * @return $this
     */
    public function withQuery(QueryParameters $query): self;

    /**
     * Eager load resources using the provided JSON:API include paths.
     *
     * Manually sets the eager load paths for the building action. This can
     * be used to override the query parameters already set, or to specify the
     * include paths when manually calling building actions.
     *
     * @param IncludePaths|RelationshipPath|array|string|null $includePaths
     * @return $this
     */
    public function with($includePaths): self;
}
