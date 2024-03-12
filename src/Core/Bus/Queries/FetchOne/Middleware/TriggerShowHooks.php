<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Bus\Queries\FetchOne\Middleware;

use Closure;
use LaravelJsonApi\Core\Bus\Queries\FetchOne\FetchOneQuery;
use LaravelJsonApi\Core\Bus\Queries\FetchOne\HandlesFetchOneQueries;
use LaravelJsonApi\Core\Bus\Queries\Result;
use RuntimeException;

class TriggerShowHooks implements HandlesFetchOneQueries
{
    /**
     * @inheritDoc
     */
    public function handle(FetchOneQuery $query, Closure $next): Result
    {
        $hooks = $query->hooks();

        if ($hooks === null) {
            return $next($query);
        }

        $request = $query->request();

        if ($request === null) {
            throw new RuntimeException('Show hooks require a request to be set on the query.');
        }

        $hooks->reading($request, $query->toQueryParams());

        /** @var Result $result */
        $result = $next($query);

        if ($result->didSucceed()) {
            $hooks->read($result->payload()->data, $request, $query->toQueryParams());
        }

        return $result;
    }
}
