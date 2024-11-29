<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Bus\Commands\UpdateRelationship\Middleware;

use Closure;
use LaravelJsonApi\Contracts\Auth\ResourceAuthorizerFactory;
use LaravelJsonApi\Core\Bus\Commands\Result;
use LaravelJsonApi\Core\Bus\Commands\UpdateRelationship\HandlesUpdateRelationshipCommands;
use LaravelJsonApi\Core\Bus\Commands\UpdateRelationship\UpdateRelationshipCommand;

final readonly class AuthorizeUpdateRelationshipCommand implements HandlesUpdateRelationshipCommands
{
    /**
     * AuthorizeUpdateRelationshipCommand constructor
     *
     * @param ResourceAuthorizerFactory $authorizerFactory
     */
    public function __construct(private ResourceAuthorizerFactory $authorizerFactory)
    {
    }

    /**
     * @inheritDoc
     */
    public function handle(UpdateRelationshipCommand $command, Closure $next): Result
    {
        $errors = null;

        if ($command->mustAuthorize()) {
            $errors = $this->authorizerFactory
                ->make($command->type())
                ->updateRelationship($command->request(), $command->modelOrFail(), $command->fieldName());
        }

        if ($errors) {
            return Result::failed($errors);
        }

        return $next($command);
    }
}
