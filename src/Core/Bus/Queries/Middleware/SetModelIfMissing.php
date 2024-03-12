<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
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
