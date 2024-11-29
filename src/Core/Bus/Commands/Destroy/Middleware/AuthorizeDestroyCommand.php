<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Bus\Commands\Destroy\Middleware;

use Closure;
use LaravelJsonApi\Contracts\Auth\ResourceAuthorizerFactory;
use LaravelJsonApi\Core\Bus\Commands\Destroy\DestroyCommand;
use LaravelJsonApi\Core\Bus\Commands\Destroy\HandlesDestroyCommands;
use LaravelJsonApi\Core\Bus\Commands\Result;

final readonly class AuthorizeDestroyCommand implements HandlesDestroyCommands
{
    /**
     * AuthorizeDestroyCommand constructor
     *
     * @param ResourceAuthorizerFactory $authorizerFactory
     */
    public function __construct(private ResourceAuthorizerFactory $authorizerFactory)
    {
    }

    /**
     * @inheritDoc
     */
    public function handle(DestroyCommand $command, Closure $next): Result
    {
        $errors = null;

        if ($command->mustAuthorize()) {
            $errors = $this->authorizerFactory
                ->make($command->type())
                ->destroy($command->request(), $command->modelOrFail());
        }

        if ($errors) {
            return Result::failed($errors);
        }

        return $next($command);
    }
}
