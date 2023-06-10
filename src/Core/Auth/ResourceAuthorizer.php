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

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer;
use LaravelJsonApi\Contracts\Auth\Authorizer as AuthorizerContract;
use LaravelJsonApi\Core\Document\ErrorList;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ResourceAuthorizer
{
    /**
     * ResourceAuthorizer constructor
     *
     * @param AuthorizerContract $authorizer
     * @param string $modelClass
     */
    public function __construct(
        private readonly AuthorizerContract $authorizer,
        private readonly string $modelClass,
    ) {
    }

    /**
     * Authorize a JSON:API store operation.
     *
     * @param Request|null $request
     * @return ErrorList|null
     * @throws AuthorizationException
     * @throws AuthenticationException
     */
    public function store(?Request $request): ?ErrorList
    {
        $passes = $this->authorizer->store(
            $request,
            $this->modelClass,
        );

        return $passes ? null : $this->failed();
    }

    /**
     * Authorize a JSON:API store operation or fail.
     *
     * @param Request|null $request
     * @return void
     * @throws AuthorizationException
     * @throws AuthenticationException
     * @throws HttpExceptionInterface
     */
    public function storeOrFail(?Request $request): void
    {
        if ($errors = $this->store($request)) {
            throw new JsonApiException($errors);
        }
    }

    /**
     * Authorize a JSON:API show query.
     *
     * @param Request|null $request
     * @param object $model
     * @return ErrorList|null
     * @throws AuthorizationException
     * @throws AuthenticationException
     */
    public function show(?Request $request, object $model): ?ErrorList
    {
        $passes = $this->authorizer->show(
            $request,
            $model,
        );

        return $passes ? null : $this->failed();
    }

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
    public function showOrFail(?Request $request, object $model): void
    {
        if ($errors = $this->show($request, $model)) {
            throw new JsonApiException($errors);
        }
    }

    /**
     * @return ErrorList
     * @throws AuthorizationException
     * @throws AuthenticationException
     * @throws HttpExceptionInterface
     */
    private function failed(): ErrorList
    {
        return ErrorList::cast(
            $this->authorizer->failed(),
        );
    }
}
