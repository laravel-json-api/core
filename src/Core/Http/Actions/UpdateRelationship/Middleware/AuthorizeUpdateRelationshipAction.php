<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Http\Actions\UpdateRelationship\Middleware;

use Closure;
use LaravelJsonApi\Core\Auth\ResourceAuthorizerFactory;
use LaravelJsonApi\Core\Http\Actions\UpdateRelationship\HandlesUpdateRelationshipActions;
use LaravelJsonApi\Core\Http\Actions\UpdateRelationship\UpdateRelationshipActionInput;
use LaravelJsonApi\Core\Responses\RelationshipResponse;

class AuthorizeUpdateRelationshipAction implements HandlesUpdateRelationshipActions
{
    /**
     * AuthorizeUpdateRelationshipAction constructor
     *
     * @param ResourceAuthorizerFactory $authorizerFactory
     */
    public function __construct(private readonly ResourceAuthorizerFactory $authorizerFactory)
    {
    }

    /**
     * @inheritDoc
     */
    public function handle(UpdateRelationshipActionInput $action, Closure $next): RelationshipResponse
    {
        $this->authorizerFactory->make($action->type())->updateRelationshipOrFail(
            $action->request(),
            $action->modelOrFail(),
            $action->fieldName(),
        );

        return $next($action);
    }
}
