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

namespace LaravelJsonApi\Core\Http\Actions\Middleware;

use Closure;
use LaravelJsonApi\Contracts\Resources\Container;
use LaravelJsonApi\Core\Http\Actions\ActionInput;
use LaravelJsonApi\Core\Http\Actions\IsIdentifiable;
use LaravelJsonApi\Core\Responses\DataResponse;

class LookupResourceIdIfNotSet
{
    /**
     * LookupResourceIdIfNotSet constructor
     *
     * @param Container $resources
     */
    public function __construct(private readonly Container $resources)
    {
    }

    /**
     * Set the resource id on the action, if not set.
     *
     * @param IsIdentifiable&ActionInput $action
     * @param Closure $next
     * @return DataResponse
     */
    public function handle(ActionInput&IsIdentifiable $action, Closure $next): DataResponse
    {
        if ($action->id() === null) {
            $action = $action->withId(
                $this->resources->idForType(
                    $action->type(),
                    $action->modelOrFail(),
                ),
            );
        }

        return $next($action);
    }
}