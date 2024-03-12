<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Bus\Queries\FetchRelationship\Middleware;

use Closure;
use LaravelJsonApi\Core\Bus\Queries\FetchRelationship\FetchRelationshipQuery;
use LaravelJsonApi\Core\Bus\Queries\FetchRelationship\HandlesFetchRelationshipQueries;
use LaravelJsonApi\Core\Bus\Queries\Result;
use RuntimeException;

class TriggerShowRelationshipHooks implements HandlesFetchRelationshipQueries
{
    /**
     * @inheritDoc
     */
    public function handle(FetchRelationshipQuery $query, Closure $next): Result
    {
        $hooks = $query->hooks();

        if ($hooks === null) {
            return $next($query);
        }

        $request = $query->request();
        $model = $query->model();
        $fieldName = $query->fieldName();

        if ($request === null || $model === null) {
            throw new RuntimeException('Show relationship hooks require a request and model to be set on the query.');
        }

        $hooks->readingRelationship($model, $fieldName, $request, $query->toQueryParams());

        /** @var Result $result */
        $result = $next($query);

        if ($result->didSucceed()) {
            $hooks->readRelationship(
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
