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
use LaravelJsonApi\Core\Bus\Queries\IsIdentifiable;
use LaravelJsonApi\Core\Bus\Queries\IsRelatable;
use LaravelJsonApi\Core\Bus\Queries\Query;
use LaravelJsonApi\Core\Bus\Queries\Result;
use LaravelJsonApi\Core\Document\Error;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class LookupModelIfRequired
{
    /**
     * LookupModelIfRequired constructor
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
        if ($query->model() === null && $this->mustLoadModel($query)) {
            $model = $this->store->find(
                $query->type(),
                $query->id() ?? throw new RuntimeException('Expecting a resource id to be set.'),
            );

            if ($model === null) {
                return Result::failed(
                    Error::make()->setStatus(Response::HTTP_NOT_FOUND)
                );
            }

            $query = $query->withModel($model);
        }

        return $next($query);
    }

    /**
     * Must the model be loaded for the query?
     *
     * We must load the model in the following scenarios:
     *
     * - If the query is going to be authorized, so we can pass the model to the authorizer.
     * - If the query is fetching a relationship, as we need the model for the relationship responses.
     *
     * @param Query $query
     * @return bool
     */
    private function mustLoadModel(Query $query): bool
    {
        return $query->mustAuthorize() || $query instanceof IsRelatable;
    }
}
