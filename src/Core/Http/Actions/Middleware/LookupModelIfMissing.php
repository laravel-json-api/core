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
use Illuminate\Contracts\Support\Responsable;
use LaravelJsonApi\Contracts\Store\Store;
use LaravelJsonApi\Core\Document\Error;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Core\Http\Actions\Input\ActionInput;
use LaravelJsonApi\Core\Http\Actions\Input\IsIdentifiable;
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
     * Set the model on the action if it is not set.
     *
     * @param IsIdentifiable&ActionInput $action
     * @param Closure $next
     * @return Responsable
     * @throws JsonApiException
     */
    public function handle(ActionInput&IsIdentifiable $action, Closure $next): Responsable
    {
        if ($action->model() === null) {
            $model = $this->store->find(
                $action->type(),
                $action->id(),
            );

            if ($model === null) {
                throw new JsonApiException(
                    Error::make()->setStatus(Response::HTTP_NOT_FOUND),
                );
            }

            $action = $action->withModel($model);
        }

        return $next($action);
    }
}
