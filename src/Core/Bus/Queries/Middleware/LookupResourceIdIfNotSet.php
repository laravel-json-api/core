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
use LaravelJsonApi\Contracts\Resources\Factory as ResourceFactory;
use LaravelJsonApi\Core\Bus\Queries\IsIdentifiable;
use LaravelJsonApi\Core\Bus\Queries\Query;
use LaravelJsonApi\Core\Bus\Queries\Result;
use RuntimeException;

class LookupResourceIdIfNotSet
{
    /**
     * LookupResourceIdIfNotSet constructor
     *
     * @param ResourceFactory $resources
     */
    public function __construct(private readonly ResourceFactory $resources)
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
        if ($query->id() === null) {
            $resource = $this->resources
                ->createResource($query->modelOrFail());

            if ($query->type()->value !== $resource->type()) {
                throw new RuntimeException(sprintf(
                    'Expecting resource type "%s" but provided model is of type "%s".',
                    $query->type(),
                    $resource->type(),
                ));
            }

            $query = $query->withId($resource->id());
        }

        return $next($query);
    }
}
