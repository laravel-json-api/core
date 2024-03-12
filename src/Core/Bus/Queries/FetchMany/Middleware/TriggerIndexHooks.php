<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Bus\Queries\FetchMany\Middleware;

use Closure;
use LaravelJsonApi\Core\Bus\Queries\FetchMany\FetchManyQuery;
use LaravelJsonApi\Core\Bus\Queries\FetchMany\HandlesFetchManyQueries;
use LaravelJsonApi\Core\Bus\Queries\Result;
use RuntimeException;

class TriggerIndexHooks implements HandlesFetchManyQueries
{
    /**
     * @inheritDoc
     */
    public function handle(FetchManyQuery $query, Closure $next): Result
    {
        $hooks = $query->hooks();

        if ($hooks === null) {
            return $next($query);
        }

        $request = $query->request();

        if ($request === null) {
            throw new RuntimeException('Index hooks require a request to be set on the query.');
        }

        $hooks->searching($request, $query->toQueryParams());

        /** @var Result $result */
        $result = $next($query);

        if ($result->didSucceed()) {
            $hooks->searched($result->payload()->data, $request, $query->toQueryParams());
        }

        return $result;
    }
}
