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

namespace LaravelJsonApi\Core\Http\Controllers\Hooks;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use LaravelJsonApi\Contracts\Http\Controllers\Hooks\ShowImplementation;
use LaravelJsonApi\Contracts\Http\Controllers\Hooks\StoreImplementation;
use LaravelJsonApi\Contracts\Query\QueryParameters;
use RuntimeException;

class HooksImplementation implements StoreImplementation, ShowImplementation
{
    /**
     * HooksImplementation constructor
     *
     * @param object $target
     */
    public function __construct(private readonly object $target)
    {
    }

    /**
     * Execute a hook, if the target implements the method.
     *
     * @param string $method
     * @param mixed ...$arguments
     * @return void
     * @throws HttpResponseException
     */
    public function __invoke(string $method, mixed ...$arguments): void
    {
        if (!method_exists($this->target, $method)) {
            return;
        }

        $response = $this->target->$method(...$arguments);

        if ($response instanceof Responsable) {
            foreach ($arguments as $arg) {
                if ($arg instanceof Request) {
                    $response = $response->toResponse($arg);
                    break;
                }
            }
        }

        if ($response instanceof Response) {
            throw new HttpResponseException($response);
        }

        throw new RuntimeException(sprintf(
            'Invalid return argument from "%s" hook - return value must be a response or responsable object.',
            $method,
        ));
    }

    /**
     * @inheritDoc
     */
    public function reading(Request $request, ?object $model): void
    {
        $this('reading', $request, $model);
    }

    /**
     * @inheritDoc
     */
    public function read(?object $model, Request $request): void
    {
        $this('read', $model, $request);
    }

    /**
     * @inheritDoc
     */
    public function saving(?object $model, Request $request, QueryParameters $parameters): void
    {
        $this('saving', $model, $request, $parameters);
    }

    /**
     * @inheritDoc
     */
    public function saved(object $model, Request $request, QueryParameters $parameters): void
    {
        $this('saved', $model, $request, $parameters);
    }

    /**
     * @inheritDoc
     */
    public function creating(Request $request, QueryParameters $parameters): void
    {
        $this('creating', $request, $parameters);
    }

    /**
     * @inheritDoc
     */
    public function created(object $model, Request $request, QueryParameters $parameters): void
    {
        $this('created', $request, $parameters);
    }
}
