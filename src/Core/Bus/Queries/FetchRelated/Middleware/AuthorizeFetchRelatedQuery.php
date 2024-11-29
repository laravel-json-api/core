<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Bus\Queries\FetchRelated\Middleware;

use Closure;
use LaravelJsonApi\Contracts\Auth\ResourceAuthorizerFactory;
use LaravelJsonApi\Core\Bus\Queries\FetchRelated\FetchRelatedQuery;
use LaravelJsonApi\Core\Bus\Queries\FetchRelated\HandlesFetchRelatedQueries;
use LaravelJsonApi\Core\Bus\Queries\Result;

final readonly class AuthorizeFetchRelatedQuery implements HandlesFetchRelatedQueries
{
    /**
     * AuthorizeFetchRelatedQuery constructor
     *
     * @param ResourceAuthorizerFactory $authorizerFactory
     */
    public function __construct(private ResourceAuthorizerFactory $authorizerFactory)
    {
    }

    /**
     * @inheritDoc
     */
    public function handle(FetchRelatedQuery $query, Closure $next): Result
    {
        $errors = null;

        if ($query->mustAuthorize()) {
            $errors = $this->authorizerFactory
                ->make($query->type())
                ->showRelated($query->request(), $query->modelOrFail(), $query->fieldName());
        }

        if ($errors) {
            return Result::failed($errors);
        }

        return $next($query);
    }
}
