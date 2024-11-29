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
use LaravelJsonApi\Contracts\Auth\ResourceAuthorizerFactory;
use LaravelJsonApi\Core\Bus\Queries\FetchOne\FetchOneQuery;
use LaravelJsonApi\Core\Bus\Queries\FetchOne\HandlesFetchOneQueries;
use LaravelJsonApi\Core\Bus\Queries\Result;

final readonly class AuthorizeFetchOneQuery implements HandlesFetchOneQueries
{
    /**
     * AuthorizeFetchOneQuery constructor
     *
     * @param ResourceAuthorizerFactory $authorizerFactory
     */
    public function __construct(private ResourceAuthorizerFactory $authorizerFactory)
    {
    }

    /**
     * @inheritDoc
     */
    public function handle(FetchOneQuery $query, Closure $next): Result
    {
        $errors = null;

        if ($query->mustAuthorize()) {
            $errors = $this->authorizerFactory
                ->make($query->type())
                ->show($query->request(), $query->modelOrFail());
        }

        if ($errors) {
            return Result::failed($errors);
        }

        return $next($query);
    }
}
