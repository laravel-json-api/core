<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Bus\Commands\Update\Middleware;

use Closure;
use LaravelJsonApi\Contracts\Auth\ResourceAuthorizerFactory;
use LaravelJsonApi\Core\Bus\Commands\Result;
use LaravelJsonApi\Core\Bus\Commands\Update\HandlesUpdateCommands;
use LaravelJsonApi\Core\Bus\Commands\Update\UpdateCommand;

final readonly class AuthorizeUpdateCommand implements HandlesUpdateCommands
{
    /**
     * AuthorizeUpdateCommand constructor
     *
     * @param ResourceAuthorizerFactory $authorizerFactory
     */
    public function __construct(private ResourceAuthorizerFactory $authorizerFactory)
    {
    }

    /**
     * @inheritDoc
     */
    public function handle(UpdateCommand $command, Closure $next): Result
    {
        $errors = null;

        if ($command->mustAuthorize()) {
            $errors = $this->authorizerFactory
                ->make($command->type())
                ->update($command->request(), $command->modelOrFail());
        }

        if ($errors) {
            return Result::failed($errors);
        }

        return $next($command);
    }
}
