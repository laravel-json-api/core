<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Bus\Queries\FetchOne;

use Closure;
use LaravelJsonApi\Core\Bus\Queries\Result;

interface HandlesFetchOneQueries
{
    /**
     * Handle a "fetch one" query.
     *
     * @param FetchOneQuery $query
     * @param Closure $next
     * @return Result
     */
    public function handle(FetchOneQuery $query, Closure $next): Result;
}
