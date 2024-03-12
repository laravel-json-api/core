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
use LaravelJsonApi\Core\Auth\ResourceAuthorizerFactory;
use LaravelJsonApi\Core\Bus\Queries\FetchMany\FetchManyQuery;
use LaravelJsonApi\Core\Bus\Queries\FetchMany\HandlesFetchManyQueries;
use LaravelJsonApi\Core\Bus\Queries\Result;

class AuthorizeFetchManyQuery implements HandlesFetchManyQueries
{
    /**
     * AuthorizeFetchOneQuery constructor
     *
     * @param ResourceAuthorizerFactory $authorizerFactory
     */
    public function __construct(private readonly ResourceAuthorizerFactory $authorizerFactory)
    {
    }

    /**
     * @inheritDoc
     */
    public function handle(FetchManyQuery $query, Closure $next): Result
    {
        $errors = null;

        if ($query->mustAuthorize()) {
            $errors = $this->authorizerFactory
                ->make($query->type())
                ->index($query->request());
        }

        if ($errors) {
            return Result::failed($errors);
        }

        return $next($query);
    }
}
