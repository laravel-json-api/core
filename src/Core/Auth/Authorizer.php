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

namespace LaravelJsonApi\Core\Auth;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer as AuthorizerContract;
use LaravelJsonApi\Contracts\Schema\Schema;
use LaravelJsonApi\Core\JsonApiService;
use LaravelJsonApi\Core\Store\LazyRelation;
use LaravelJsonApi\Core\Support\Str;

class Authorizer implements AuthorizerContract
{

    /**
     * @var Gate
     */
    private Gate $gate;

    /**
     * @var JsonApiService
     */
    private JsonApiService $service;

    /**
     * AnonymousAuthorizer constructor.
     *
     * @param Gate $gate
     * @param JsonApiService $service
     */
    public function __construct(Gate $gate, JsonApiService $service)
    {
        $this->gate = $gate;
        $this->service = $service;
    }

    /**
     * @inheritDoc
     */
    public function index(Request $request, string $modelClass): bool
    {
        if ($this->mustAuthorize()) {
            return $this->gate->check(
                'viewAny',
                $modelClass
            );
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function store(Request $request, string $modelClass): bool
    {
        if ($this->mustAuthorize()) {
            return $this->gate->check(
                'create',
                $modelClass
            );
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function show(Request $request, object $model): bool
    {
        if ($this->mustAuthorize()) {
            return $this->gate->check(
                'view',
                $model
            );
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function update(Request $request, object $model): bool
    {
        if ($this->mustAuthorize()) {
            return $this->gate->check(
                'update',
                $model
            );
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function destroy(Request $request, object $model): bool
    {
        if ($this->mustAuthorize()) {
            return $this->gate->check(
                'delete',
                $model
            );
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function showRelated(Request $request, object $model, string $fieldName): bool
    {
        if ($this->mustAuthorize()) {
            return $this->gate->check(
                'view' . Str::classify($fieldName),
                $model
            );
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function showRelationship(Request $request, object $model, string $fieldName): bool
    {
        return $this->showRelated($request, $model, $fieldName);
    }

    /**
     * @inheritDoc
     */
    public function updateRelationship(Request $request, object $model, string $fieldName): bool
    {
        if ($this->mustAuthorize()) {
            return $this->gate->check(
                'update' . Str::classify($fieldName),
                [$model, $this->createRelation($request, $fieldName)]
            );
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function attachRelationship(Request $request, object $model, string $fieldName): bool
    {
        if ($this->mustAuthorize()) {
            return $this->gate->check(
                'attach' . Str::classify($fieldName),
                [$model, $this->createRelation($request, $fieldName)]
            );
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function detachRelationship(Request $request, object $model, string $fieldName): bool
    {
        if ($this->mustAuthorize()) {
            return $this->gate->check(
                'detach' . Str::classify($fieldName),
                [$model, $this->createRelation($request, $fieldName)]
            );
        }

        return true;
    }

    /**
     * Create a lazy relation object.
     *
     * @param Request $request
     * @param string $fieldName
     * @return LazyRelation
     */
    private function createRelation(Request $request, string $fieldName): LazyRelation
    {
        return new LazyRelation(
            $this->service->server(),
            $this->schema()->relationship($fieldName),
            $request->json()->all()
        );
    }

    /**
     * Should default resource authorization be run?
     *
     * For authorization to be triggered, authorization must
     * be enabled for both the server AND the resource schema.
     *
     * @return bool
     */
    private function mustAuthorize(): bool
    {
        if ($this->service->server()->authorizable()) {
            return $this->schema()->authorizable();
        }

        return false;
    }

    /**
     * @return Schema
     */
    private function schema(): Schema
    {
        return $this->service->route()->schema();
    }
}
