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

namespace LaravelJsonApi\Core\Bus\Commands\Middleware;

use Closure;
use LaravelJsonApi\Contracts\Store\Store;
use LaravelJsonApi\Core\Bus\Commands\Command;
use LaravelJsonApi\Core\Bus\Commands\IsIdentifiable;
use LaravelJsonApi\Core\Bus\Commands\Result;
use LaravelJsonApi\Core\Document\Error;
use Symfony\Component\HttpFoundation\Response;

class LookupModelIfMissing
{
    /**
     * LookupModelIfMissing constructor
     *
     * @param Store $store
     */
    public function __construct(private readonly Store $store)
    {
    }

    /**
     * Handle an identifiable command.
     *
     * @param IsIdentifiable&Command $command
     * @param Closure $next
     * @return Result
     */
    public function handle(Command&IsIdentifiable $command, Closure $next): Result
    {
        if ($command->model() === null) {
            $model = $this->store->find(
                $command->type(),
                $command->id(),
            );

            if ($model === null) {
                return Result::failed(
                    Error::make()->setStatus(Response::HTTP_NOT_FOUND)
                );
            }

            $command = $command->withModel($model);
        }

        return $next($command);
    }
}
