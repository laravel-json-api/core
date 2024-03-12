<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Bus\Commands\DetachRelationship\Middleware;

use Closure;
use LaravelJsonApi\Core\Auth\ResourceAuthorizerFactory;
use LaravelJsonApi\Core\Bus\Commands\DetachRelationship\DetachRelationshipCommand;
use LaravelJsonApi\Core\Bus\Commands\DetachRelationship\HandlesDetachRelationshipCommands;
use LaravelJsonApi\Core\Bus\Commands\Result;

class AuthorizeDetachRelationshipCommand implements HandlesDetachRelationshipCommands
{
    /**
     * AuthorizeAttachRelationshipCommand constructor
     *
     * @param ResourceAuthorizerFactory $authorizerFactory
     */
    public function __construct(private readonly ResourceAuthorizerFactory $authorizerFactory)
    {
    }

    /**
     * @inheritDoc
     */
    public function handle(DetachRelationshipCommand $command, Closure $next): Result
    {
        $errors = null;

        if ($command->mustAuthorize()) {
            $errors = $this->authorizerFactory
                ->make($command->type())
                ->detachRelationship($command->request(), $command->modelOrFail(), $command->fieldName());
        }

        if ($errors) {
            return Result::failed($errors);
        }

        return $next($command);
    }
}
