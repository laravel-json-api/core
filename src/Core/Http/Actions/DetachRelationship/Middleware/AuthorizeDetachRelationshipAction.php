<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Http\Actions\DetachRelationship\Middleware;

use Closure;
use LaravelJsonApi\Contracts\Auth\ResourceAuthorizerFactory;
use LaravelJsonApi\Core\Http\Actions\DetachRelationship\DetachRelationshipActionInput;
use LaravelJsonApi\Core\Http\Actions\DetachRelationship\HandlesDetachRelationshipActions;
use LaravelJsonApi\Core\Responses\NoContentResponse;
use LaravelJsonApi\Core\Responses\RelationshipResponse;

final readonly class AuthorizeDetachRelationshipAction implements HandlesDetachRelationshipActions
{
    /**
     * AuthorizeDetachRelationshipAction constructor
     *
     * @param ResourceAuthorizerFactory $authorizerFactory
     */
    public function __construct(private ResourceAuthorizerFactory $authorizerFactory)
    {
    }

    /**
     * @inheritDoc
     */
    public function handle(
        DetachRelationshipActionInput $action,
        Closure $next,
    ): RelationshipResponse|NoContentResponse
    {
        $this->authorizerFactory->make($action->type())->detachRelationshipOrFail(
            $action->request(),
            $action->modelOrFail(),
            $action->fieldName(),
        );

        return $next($action);
    }
}
