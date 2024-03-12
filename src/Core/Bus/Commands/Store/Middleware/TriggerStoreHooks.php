<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Bus\Commands\Store\Middleware;

use Closure;
use LaravelJsonApi\Core\Bus\Commands\Result;
use LaravelJsonApi\Core\Bus\Commands\Store\HandlesStoreCommands;
use LaravelJsonApi\Core\Bus\Commands\Store\StoreCommand;
use RuntimeException;

class TriggerStoreHooks implements HandlesStoreCommands
{
    /**
     * @inheritDoc
     */
    public function handle(StoreCommand $command, Closure $next): Result
    {
        $hooks = $command->hooks();

        if ($hooks === null) {
            return $next($command);
        }

        $request = $command->request() ?? throw new RuntimeException('Hooks require a request to be set.');
        $query = $command->query() ?? throw new RuntimeException('Hooks require query parameters to be set.');

        $hooks->saving(null, $request, $query);
        $hooks->creating($request, $query);

        /** @var Result $result */
        $result = $next($command);

        if ($result->didSucceed()) {
            $model = $result->payload()->data;
            $hooks->created($model, $request, $query);
            $hooks->saved($model, $request, $query);
        }

        return $result;
    }
}
