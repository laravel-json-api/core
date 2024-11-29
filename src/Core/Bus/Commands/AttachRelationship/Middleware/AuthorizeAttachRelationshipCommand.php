<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Bus\Commands\AttachRelationship\Middleware;

use Closure;
use LaravelJsonApi\Contracts\Auth\ResourceAuthorizerFactory;
use LaravelJsonApi\Core\Bus\Commands\AttachRelationship\AttachRelationshipCommand;
use LaravelJsonApi\Core\Bus\Commands\AttachRelationship\HandlesAttachRelationshipCommands;
use LaravelJsonApi\Core\Bus\Commands\Result;

final readonly class AuthorizeAttachRelationshipCommand implements HandlesAttachRelationshipCommands
{
    /**
     * AuthorizeAttachRelationshipCommand constructor
     *
     * @param ResourceAuthorizerFactory $authorizerFactory
     */
    public function __construct(private ResourceAuthorizerFactory $authorizerFactory)
    {
    }

    /**
     * @inheritDoc
     */
    public function handle(AttachRelationshipCommand $command, Closure $next): Result
    {
        $errors = null;

        if ($command->mustAuthorize()) {
            $errors = $this->authorizerFactory
                ->make($command->type())
                ->attachRelationship($command->request(), $command->modelOrFail(), $command->fieldName());
        }

        if ($errors) {
            return Result::failed($errors);
        }

        return $next($command);
    }
}
