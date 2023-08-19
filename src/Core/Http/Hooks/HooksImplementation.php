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

namespace LaravelJsonApi\Core\Http\Hooks;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Http\Hooks\AttachRelationshipImplementation;
use LaravelJsonApi\Contracts\Http\Hooks\DestroyImplementation;
use LaravelJsonApi\Contracts\Http\Hooks\DetachRelationshipImplementation;
use LaravelJsonApi\Contracts\Http\Hooks\IndexImplementation;
use LaravelJsonApi\Contracts\Http\Hooks\ShowImplementation;
use LaravelJsonApi\Contracts\Http\Hooks\ShowRelatedImplementation;
use LaravelJsonApi\Contracts\Http\Hooks\ShowRelationshipImplementation;
use LaravelJsonApi\Contracts\Http\Hooks\StoreImplementation;
use LaravelJsonApi\Contracts\Http\Hooks\UpdateImplementation;
use LaravelJsonApi\Contracts\Http\Hooks\UpdateRelationshipImplementation;
use LaravelJsonApi\Contracts\Query\QueryParameters;
use LaravelJsonApi\Core\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class HooksImplementation implements
    IndexImplementation,
    StoreImplementation,
    ShowImplementation,
    UpdateImplementation,
    DestroyImplementation,
    ShowRelatedImplementation,
    ShowRelationshipImplementation,
    UpdateRelationshipImplementation,
    AttachRelationshipImplementation,
    DetachRelationshipImplementation
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
    public function updating(object $model, Request $request, QueryParameters $query): void
    {
        $this('updating', $model, $request, $query);
    }

    /**
     * @inheritDoc
     */
    public function updated(object $model, Request $request, QueryParameters $query): void
    {
        $this('updated', $model, $request, $query);
    }

    /**
     * @inheritDoc
     */
    public function deleting(object $model, Request $request): void
    {
        $this('deleting', $model, $request);
    }

    /**
     * @inheritDoc
     */
    public function deleted(object $model, Request $request): void
    {
        $this('deleted', $model, $request);
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

    /**
     * @inheritDoc
     */
    public function updatingRelationship(
        object $model,
        string $fieldName,
        Request $request,
        QueryParameters $query,
    ): void
    {
        $method = 'updating' . Str::classify($fieldName);

        $this($method, $model, $request, $query);
    }

    /**
     * @inheritDoc
     */
    public function updatedRelationship(
        object $model,
        string $fieldName,
        mixed $related,
        Request $request,
        QueryParameters $query,
    ): void
    {
        $method = 'updated' . Str::classify($fieldName);

        $this($method, $model, $related, $request, $query);
    }

    /**
     * @inheritDoc
     */
    public function attachingRelationship(
        object $model,
        string $fieldName,
        Request $request,
        QueryParameters $query,
    ): void
    {
        $method = 'attaching' . Str::classify($fieldName);

        $this($method, $model, $request, $query);
    }

    /**
     * @inheritDoc
     */
    public function attachedRelationship(
        object $model,
        string $fieldName,
        mixed $related,
        Request $request,
        QueryParameters $query,
    ): void
    {
        $method = 'attached' . Str::classify($fieldName);

        $this($method, $model, $related, $request, $query);
    }

    /**
     * @inheritDoc
     */
    public function detachingRelationship(
        object $model,
        string $fieldName,
        Request $request,
        QueryParameters $query,
    ): void
    {
        $method = 'detaching' . Str::classify($fieldName);

        $this($method, $model, $request, $query);
    }

    /**
     * @inheritDoc
     */
    public function detachedRelationship(
        object $model,
        string $fieldName,
        mixed $related,
        Request $request,
        QueryParameters $query,
    ): void
    {
        $method = 'detached' . Str::classify($fieldName);

        $this($method, $model, $related, $request, $query);
    }
}
