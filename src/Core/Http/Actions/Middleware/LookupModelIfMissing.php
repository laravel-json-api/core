<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
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
