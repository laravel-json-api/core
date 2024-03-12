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
use LaravelJsonApi\Core\Bus\Commands\Result;
use LaravelJsonApi\Core\Bus\Commands\Update\HandlesUpdateCommands;
use LaravelJsonApi\Core\Bus\Commands\Update\UpdateCommand;
use RuntimeException;

class TriggerUpdateHooks implements HandlesUpdateCommands
{
    /**
     * @inheritDoc
     */
    public function handle(UpdateCommand $command, Closure $next): Result
    {
        $hooks = $command->hooks();

        if ($hooks === null) {
            return $next($command);
        }

        $request = $command->request() ?? throw new RuntimeException('Hooks require a request to be set.');
        $query = $command->query() ?? throw new RuntimeException('Hooks require query parameters to be set.');
        $model = $command->modelOrFail();

        $hooks->saving($model, $request, $query);
        $hooks->updating($model, $request, $query);

        /** @var Result $result */
        $result = $next($command);

        if ($result->didSucceed()) {
            $model = $result->payload()->data ?? $model;
            $hooks->updated($model, $request, $query);
            $hooks->saved($model, $request, $query);
        }

        return $result;
    }
}
