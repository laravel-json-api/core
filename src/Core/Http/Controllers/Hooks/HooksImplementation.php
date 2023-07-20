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
use LaravelJsonApi\Contracts\Http\Controllers\Hooks\IndexImplementation;
use LaravelJsonApi\Contracts\Http\Controllers\Hooks\ShowImplementation;
use LaravelJsonApi\Contracts\Http\Controllers\Hooks\ShowRelatedImplementation;
use LaravelJsonApi\Contracts\Http\Controllers\Hooks\ShowRelationshipImplementation;
use LaravelJsonApi\Contracts\Http\Controllers\Hooks\StoreImplementation;
use LaravelJsonApi\Contracts\Query\QueryParameters;
use LaravelJsonApi\Core\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class HooksImplementation implements
    IndexImplementation,
    StoreImplementation,
    ShowImplementation,
    ShowRelatedImplementation,
    ShowRelationshipImplementation
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

        if ($response === null) {
            return;
        }

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
     * @param HooksImplementation $other
     * @return bool
     */
    public function equals(self $other): bool
    {
        return $this->target === $other->target;
    }

    /**
     * @inheritDoc
     */
    public function searching(Request $request, QueryParameters $query): void
    {
        $this('searching', $request, $query);
    }

    /**
     * @inheritDoc
     */
    public function searched(mixed $data, Request $request, QueryParameters $query): void
    {
        $this('searched', $data, $request, $query);
    }

    /**
     * @inheritDoc
     */
    public function reading(Request $request, QueryParameters $query): void
    {
        $this('reading', $request, $query);
    }

    /**
     * @inheritDoc
     */
    public function read(?object $model, Request $request, QueryParameters $query): void
    {
        $this('read', $model, $request, $query);
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
    public function creating(Request $request, QueryParameters $query): void
    {
        $this('creating', $request, $query);
    }

    /**
     * @inheritDoc
     */
    public function created(object $model, Request $request, QueryParameters $query): void
    {
        $this('created', $model, $request, $query);
    }

    /**
     * @inheritDoc
     */
    public function readingRelated(object $model, string $field, Request $request, QueryParameters $query): void
    {
        $method = 'readingRelated' . Str::classify($field);

        $this($method, $model, $request, $query);
    }

    /**
     * @inheritDoc
     */
    public function readRelated(
        ?object $model,
        string $field,
        mixed $related,
        Request $request,
        QueryParameters $query
    ): void
    {
        $method = 'readRelated' . Str::classify($field);

        $this($method, $model, $related, $request, $query);
    }

    /**
     * @inheritDoc
     */
    public function readingRelationship(object $model, string $field, Request $request, QueryParameters $query,): void
    {
        $method = 'reading' . Str::classify($field);

        $this($method, $model, $request, $query);
    }

    /**
     * @inheritDoc
     */
    public function readRelationship(
        ?object $model,
        string $field,
        mixed $related,
        Request $request,
        QueryParameters $query,
    ): void
    {
        $method = 'read' . Str::classify($field);

        $this($method, $model, $related, $request, $query);
    }
}