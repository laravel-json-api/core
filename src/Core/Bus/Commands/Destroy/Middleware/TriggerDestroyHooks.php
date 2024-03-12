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
use LaravelJsonApi\Core\Bus\Commands\Destroy\DestroyCommand;
use LaravelJsonApi\Core\Bus\Commands\Destroy\HandlesDestroyCommands;
use LaravelJsonApi\Core\Bus\Commands\Result;
use RuntimeException;

class TriggerDestroyHooks implements HandlesDestroyCommands
{
    /**
     * @inheritDoc
     */
    public function handle(DestroyCommand $command, Closure $next): Result
    {
        $hooks = $command->hooks();

        if ($hooks === null) {
            return $next($command);
        }

        $request = $command->request() ?? throw new RuntimeException('Hooks require a request to be set.');
        $model = $command->modelOrFail();

        $hooks->deleting($model, $request);

        /** @var Result $result */
        $result = $next($command);

        if ($result->didSucceed()) {
            $hooks->deleted($model, $request);
        }

        return $result;
    }
}
