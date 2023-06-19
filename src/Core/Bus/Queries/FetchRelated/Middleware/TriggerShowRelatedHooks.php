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

namespace LaravelJsonApi\Core\Bus\Queries\FetchRelated\Middleware;

use Closure;
use LaravelJsonApi\Core\Bus\Queries\FetchRelated\FetchRelatedQuery;
use LaravelJsonApi\Core\Bus\Queries\FetchRelated\HandlesFetchRelatedQueries;
use LaravelJsonApi\Core\Bus\Queries\Result;
use RuntimeException;

class TriggerShowRelatedHooks implements HandlesFetchRelatedQueries
{
    /**
     * @inheritDoc
     */
    public function handle(FetchRelatedQuery $query, Closure $next): Result
    {
        $hooks = $query->hooks();

        if ($hooks === null) {
            return $next($query);
        }

        $request = $query->request();
        $model = $query->model();
        $fieldName = $query->fieldName();

        if ($request === null || $model === null) {
            throw new RuntimeException('Show related hooks require a request and model to be set on the query.');
        }

        $hooks->readingRelated($model, $fieldName, $request, $query->toQueryParams());

        /** @var Result $result */
        $result = $next($query);

        if ($result->didSucceed()) {
            $hooks->readRelated(
                $model,
                $fieldName,
                $result->payload()->data,
                $request,
                $query->toQueryParams(),
            );
        }

        return $result;
    }
}
