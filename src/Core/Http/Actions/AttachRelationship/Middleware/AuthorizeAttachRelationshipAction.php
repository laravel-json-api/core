<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Http\Actions\AttachRelationship\Middleware;

use Closure;
use LaravelJsonApi\Contracts\Auth\ResourceAuthorizerFactory;
use LaravelJsonApi\Core\Http\Actions\AttachRelationship\AttachRelationshipActionInput;
use LaravelJsonApi\Core\Http\Actions\AttachRelationship\HandlesAttachRelationshipActions;
use LaravelJsonApi\Core\Responses\NoContentResponse;
use LaravelJsonApi\Core\Responses\RelationshipResponse;

final readonly class AuthorizeAttachRelationshipAction implements HandlesAttachRelationshipActions
{
    /**
     * AuthorizeAttachRelationshipAction constructor
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
        AttachRelationshipActionInput $action,
        Closure $next,
    ): RelationshipResponse|NoContentResponse
    {
        $this->authorizerFactory->make($action->type())->attachRelationshipOrFail(
            $action->request(),
            $action->modelOrFail(),
            $action->fieldName(),
        );

        return $next($action);
    }
}
