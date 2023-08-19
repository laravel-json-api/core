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

namespace LaravelJsonApi\Core\Bus\Queries\Middleware;

use Closure;
use LaravelJsonApi\Contracts\Store\Store;
use LaravelJsonApi\Core\Bus\Queries\Query\IsIdentifiable;
use LaravelJsonApi\Core\Bus\Queries\Query\Query;
use LaravelJsonApi\Core\Bus\Queries\Result;
use LaravelJsonApi\Core\Store\LazyModel;

class SetModelIfMissing
{
    /**
     * SetModelIfMissing constructor
     *
     * @param Store $store
     */
    public function __construct(private readonly Store $store)
    {
    }

    /**
     * Handle an identifiable query.
     *
     * @param IsIdentifiable&Query $query
     * @param Closure $next
     * @return Result
     */
    public function handle(Query&IsIdentifiable $query, Closure $next): Result
    {
        if ($query->model() === null) {
            $query = $query->withModel(new LazyModel(
                $this->store,
                $query->type(),
                $query->id(),
            ));
        }

        return $next($query);
    }
}