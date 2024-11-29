<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Http\Actions\Store\Middleware;

use Closure;
use LaravelJsonApi\Contracts\Auth\ResourceAuthorizerFactory;
use LaravelJsonApi\Core\Http\Actions\Store\HandlesStoreActions;
use LaravelJsonApi\Core\Http\Actions\Store\StoreActionInput;
use LaravelJsonApi\Core\Responses\DataResponse;

final readonly class AuthorizeStoreAction implements HandlesStoreActions
{
    /**
     * AuthorizeStoreCommand constructor
     *
     * @param ResourceAuthorizerFactory $authorizerFactory
     */
    public function __construct(private ResourceAuthorizerFactory $authorizerFactory)
    {
    }

    /**
     * @inheritDoc
     */
    public function handle(StoreActionInput $action, Closure $next): DataResponse
    {
        $this->authorizerFactory
            ->make($action->type())
            ->storeOrFail($action->request());

        return $next($action);
    }
}
