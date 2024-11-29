<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

namespace LaravelJsonApi\Contracts\Auth;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Document\ErrorList;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

interface ResourceAuthorizer
{
    /**
     * Authorize a JSON:API index query.
     *
     * @param Request|null $request
     * @return ErrorList|null
     * @throws AuthenticationException
     * @throws AuthorizationException
     * @throws HttpExceptionInterface
     */
    public function index(?Request $request): ?ErrorList;

    /**
     * Authorize a JSON:API index query or fail.
     *
     * @param Request|null $request
     * @return void
     * @throws AuthenticationException
     * @throws AuthorizationException
     * @throws HttpExceptionInterface
     */
    public function indexOrFail(?Request $request): void;

    /**
     * Authorize a JSON:API store operation.
     *
     * @param Request|null $request
     * @return ErrorList|null
     * @throws AuthorizationException
     * @throws AuthenticationException
     * @throws HttpExceptionInterface
     */
    public function store(?Request $request): ?ErrorList;

    /**
     * Authorize a JSON:API store operation or fail.
     *
     * @param Request|null $request
     * @return void
     * @throws AuthorizationException
     * @throws AuthenticationException
     * @throws HttpExceptionInterface
     */
    public function storeOrFail(?Request $request): void;

    /**
     * Authorize a JSON:API show query.
     *
     * @param Request|null $request
     * @param object $model
     * @return ErrorList|null
     * @throws AuthorizationException
     * @throws AuthenticationException
     * @throws HttpExceptionInterface
     */
    public function show(?Request $request, object $model): ?ErrorList;

    /**
     * Authorize a JSON:API show query, or fail.
     *
     * @param Request|null $request
     * @param object $model
     * @return void
     * @throws AuthorizationException
     * @throws AuthenticationException
     * @throws HttpExceptionInterface
     */
    public function showOrFail(?Request $request, object $model): void;

    /**
     * Authorize a JSON:API update command.
     *
     * @param Request|null $request
     * @param object $model
     * @return ErrorList|null
     * @throws AuthorizationException
     * @throws AuthenticationException
     * @throws HttpExceptionInterface
     */
    public function update(?Request $request, object $model): ?ErrorList;

    /**
     * Authorize a JSON:API update command, or fail.
     *
     * @param Request|null $request
     * @param object $model
     * @return void
     * @throws AuthorizationException
     * @throws AuthenticationException
     * @throws HttpExceptionInterface
     */
    public function updateOrFail(?Request $request, object $model): void;

    /**
     * Authorize a JSON:API destroy command.
     *
     * @param Request|null $request
     * @param object $model
     * @return ErrorList|null
     * @throws AuthorizationException
     * @throws AuthenticationException
     * @throws HttpExceptionInterface
     */
    public function destroy(?Request $request, object $model): ?ErrorList;

    /**
     * Authorize a JSON:API destroy command, or fail.
     *
     * @param Request|null $request
     * @param object $model
     * @return void
     * @throws AuthorizationException
     * @throws AuthenticationException
     * @throws HttpExceptionInterface
     */
    public function destroyOrFail(?Request $request, object $model): void;

    /**
     * Authorize a JSON:API show related query.
     *
     * @param Request|null $request
     * @param object $model
     * @param string $fieldName
     * @return ErrorList|null
     * @throws AuthorizationException
     * @throws AuthenticationException
     * @throws HttpExceptionInterface
     */
    public function showRelated(?Request $request, object $model, string $fieldName): ?ErrorList;

    /**
     * Authorize a JSON:API show related query, or fail.
     *
     * @param Request|null $request
     * @param object $model
     * @param string $fieldName
     * @return void
     * @throws AuthorizationException
     * @throws AuthenticationException
     * @throws HttpExceptionInterface
     */
    public function showRelatedOrFail(?Request $request, object $model, string $fieldName): void;

    /**
     * Authorize a JSON:API show relationship query.
     *
     * @param Request|null $request
     * @param object $model
     * @param string $fieldName
     * @return ErrorList|null
     * @throws AuthorizationException
     * @throws AuthenticationException
     * @throws HttpExceptionInterface
     */
    public function showRelationship(?Request $request, object $model, string $fieldName): ?ErrorList;

    /**
     * Authorize a JSON:API show relationship query, or fail.
     *
     * @param Request|null $request
     * @param object $model
     * @param string $fieldName
     * @return void
     * @throws AuthorizationException
     * @throws AuthenticationException
     * @throws HttpExceptionInterface
     */
    public function showRelationshipOrFail(?Request $request, object $model, string $fieldName): void;

    /**
     * Authorize a JSON:API update relationship command.
     *
     * @param Request|null $request
     * @param object $model
     * @param string $fieldName
     * @return ErrorList|null
     * @throws AuthorizationException
     * @throws AuthenticationException
     * @throws HttpExceptionInterface
     */
    public function updateRelationship(?Request $request, object $model, string $fieldName): ?ErrorList;

    /**
     * Authorize a JSON:API update relationship command, or fail.
     *
     * @param Request|null $request
     * @param object $model
     * @param string $fieldName
     * @return void
     * @throws AuthorizationException
     * @throws AuthenticationException
     * @throws HttpExceptionInterface
     */
    public function updateRelationshipOrFail(?Request $request, object $model, string $fieldName): void;

    /**
     * Authorize a JSON:API attach relationship command.
     *
     * @param Request|null $request
     * @param object $model
     * @param string $fieldName
     * @return ErrorList|null
     * @throws AuthorizationException
     * @throws AuthenticationException
     * @throws HttpExceptionInterface
     */
    public function attachRelationship(?Request $request, object $model, string $fieldName): ?ErrorList;

    /**
     * Authorize a JSON:API attach relationship command, or fail.
     *
     * @param Request|null $request
     * @param object $model
     * @param string $fieldName
     * @return void
     * @throws AuthorizationException
     * @throws AuthenticationException
     * @throws HttpExceptionInterface
     */
    public function attachRelationshipOrFail(?Request $request, object $model, string $fieldName): void;

    /**
     * Authorize a JSON:API detach relationship command.
     *
     * @param Request|null $request
     * @param object $model
     * @param string $fieldName
     * @return ErrorList|null
     * @throws AuthorizationException
     * @throws AuthenticationException
     * @throws HttpExceptionInterface
     */
    public function detachRelationship(?Request $request, object $model, string $fieldName): ?ErrorList;

    /**
     * Authorize a JSON:API detach relationship command, or fail.
     *
     * @param Request|null $request
     * @param object $model
     * @param string $fieldName
     * @return void
     * @throws AuthorizationException
     * @throws AuthenticationException
     * @throws HttpExceptionInterface
     */
    public function detachRelationshipOrFail(?Request $request, object $model, string $fieldName): void;
}