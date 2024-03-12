<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Contracts\Bus\Queries;

use LaravelJsonApi\Core\Bus\Queries\Query\Query;
use LaravelJsonApi\Core\Bus\Queries\Result;

interface Dispatcher
{
    /**
     * Dispatch a JSON:API query.
     *
     * @param Query $query
     * @return Result
     */
    public function dispatch(Query $query): Result;
}
