<?php
/*
 * Copyright 2023 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
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

        $request = $command->request();
        $query = $command->query();

        if ($request === null || $query === null) {
            throw new RuntimeException(
                'Store hooks require a request and query parameters to be set on the command.',
            );
        }

        $hooks->saving(null, $request, $query);
        $hooks->creating($request, $query);

        /** @var Result $result */
        $result = $next($command);
        $model = $result->payload()->data;

        $hooks->created($model, $request, $query);
        $hooks->saved($model, $request, $query);

        return $result;
    }
}
