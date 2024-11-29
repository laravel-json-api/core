<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Auth;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer as AuthorizerContract;
use LaravelJsonApi\Contracts\Schema\Schema;
use LaravelJsonApi\Core\JsonApiService;
use LaravelJsonApi\Core\Store\LazyRelation;
use LaravelJsonApi\Core\Support\Str;

class Authorizer implements AuthorizerContract
{
    /**
     * Authorizer constructor.
     *
     * @param Guard $auth
     * @param Gate $gate
     * @param JsonApiService $service
     */
    public function __construct(
        private readonly Guard $auth,
        private readonly Gate $gate,
        private readonly JsonApiService $service,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function index(?Request $request, string $modelClass): bool|Response
    {
        if ($this->mustAuthorize()) {
            return $this->gate->inspect(
                'viewAny',
                $modelClass
            );
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function store(?Request $request, string $modelClass): bool|Response
    {
        if ($this->mustAuthorize()) {
            return $this->gate->inspect(
                'create',
                $modelClass
            );
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function show(?Request $request, object $model): bool|Response
    {
        if ($this->mustAuthorize()) {
            return $this->gate->inspect(
                'view',
                $model
            );
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function update(Request $request, object $model): bool|Response
    {
        if ($this->mustAuthorize()) {
            return $this->gate->inspect(
                'update',
                $model
            );
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function destroy(Request $request, object $model): bool|Response
    {
        if ($this->mustAuthorize()) {
            return $this->gate->inspect(
                'delete',
                $model
            );
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function showRelated(Request $request, object $model, string $fieldName): bool|Response
    {
        if ($this->mustAuthorize()) {
            return $this->gate->inspect(
                'view' . Str::classify($fieldName),
                $model
            );
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function showRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        return $this->showRelated($request, $model, $fieldName);
    }

    /**
     * @inheritDoc
     */
    public function updateRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        if ($this->mustAuthorize()) {
            return $this->gate->inspect(
                'update' . Str::classify($fieldName),
                [$model, $this->createRelation($request, $fieldName)]
            );
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function attachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        if ($this->mustAuthorize()) {
            return $this->gate->inspect(
                'attach' . Str::classify($fieldName),
                [$model, $this->createRelation($request, $fieldName)]
            );
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function detachRelationship(Request $request, object $model, string $fieldName): bool|Response
    {
        if ($this->mustAuthorize()) {
            return $this->gate->inspect(
                'detach' . Str::classify($fieldName),
                [$model, $this->createRelation($request, $fieldName)]
            );
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function failed(): never
    {
        if ($this->auth->guest()) {
            throw new AuthenticationException();
        }

        throw new AuthorizationException();
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
