<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Contracts\Auth;

use Illuminate\Auth\Access\Response;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Document\Error;
use LaravelJsonApi\Core\Document\ErrorList;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

interface Authorizer
{
    /**
     * Authorize a JSON:API index query.
     *
     * @param Request|null $request
     * @param string $modelClass
     * @return bool|Response
     */
    public function index(?Request $request, string $modelClass): bool|Response;

    /**
     * Authorize a JSON:API store operation.
     *
     * @param Request|null $request
     * @param string $modelClass
     * @return bool|Response
     */
    public function store(?Request $request, string $modelClass): bool|Response;

    /**
     * Authorize a JSON:API show query.
     *
     * @param Request|null $request
     * @param object $model
     * @return bool|Response
     */
    public function show(?Request $request, object $model): bool|Response;

    /**
     * Authorize a JSON:API update command.
     *
     * @param object $model
     * @param Request|null $request
     * @return bool|Response
     */
    public function update(?Request $request, object $model): bool|Response;

    /**
     * Authorize a JSON:API destroy command.
     *
     * @param Request|null $request
     * @param object $model
     * @return bool|Response
     */
    public function destroy(?Request $request, object $model): bool|Response;

    /**
     * Authorize a JSON:API show related query.
     *
     * @param Request|null $request
     * @param object $model
     * @param string $fieldName
     * @return bool|Response
     */
    public function showRelated(?Request $request, object $model, string $fieldName): bool|Response;

    /**
     * Authorize a JSON:API show relationship query.
     *
     * @param Request|null $request
     * @param object $model
     * @param string $fieldName
     * @return bool|Response
     */
    public function showRelationship(?Request $request, object $model, string $fieldName): bool|Response;

    /**
     * Authorize a JSON:API update relationship command.
     *
     * @param Request|null $request
     * @param object $model
     * @param string $fieldName
     * @return bool|Response
     */
    public function updateRelationship(?Request $request, object $model, string $fieldName): bool|Response;

    /**
     * Authorize a JSON:API attach relationship command.
     *
     * @param Request|null $request
     * @param object $model
     * @param string $fieldName
     * @return bool|Response
     */
    public function attachRelationship(?Request $request, object $model, string $fieldName): bool|Response;

    /**
     * Authorize a JSON:API detach relationship command.
     *
     * @param Request|null $request
     * @param object $model
     * @param string $fieldName
     * @return bool|Response
     */
    public function detachRelationship(?Request $request, object $model, string $fieldName): bool|Response;

    /**
     * Get JSON:API errors describing the failure, or throw an appropriate exception.
     *
     * @return ErrorList|Error
     * @throws AuthenticationException
     * @throws AuthorizationException
     * @throws HttpExceptionInterface
     */
    public function failed(): ErrorList|Error;
}
