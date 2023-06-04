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

namespace LaravelJsonApi\Core\Bus\Queries\FetchOne\Middleware;

use Closure;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer;
use LaravelJsonApi\Contracts\Auth\Container as AuthorizerContainer;
use LaravelJsonApi\Core\Bus\Queries\FetchOne\FetchOneQuery;
use LaravelJsonApi\Core\Bus\Queries\FetchOne\HandlesFetchOneQueries;
use LaravelJsonApi\Core\Bus\Queries\Result;
use LaravelJsonApi\Core\Document\Error;
use LaravelJsonApi\Core\Document\ErrorList;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;
use Throwable;

class AuthorizeFetchOneQuery implements HandlesFetchOneQueries
{
    /**
     * AuthorizeFetchOneQuery constructor
     *
     * @param AuthorizerContainer $authorizerContainer
     */
    public function __construct(private readonly AuthorizerContainer $authorizerContainer)
    {
    }

    /**
     * @inheritDoc
     */
    public function handle(FetchOneQuery $query, Closure $next): Result
    {
        $errors = null;

        if ($query->mustAuthorize()) {
            $errors = $this->authorize(
                $query->request(),
                $query->type(),
                $query->modelOrFail(),
            );
        }

        if ($errors) {
            return Result::failed($errors);
        }

        return $next($query);
    }

    /**
     * @param Request|null $request
     * @param ResourceType $type
     * @param object $model
     * @return ErrorList|Error|null
     * @throws Throwable
     */
    public function authorize(?Request $request, ResourceType $type, object $model): ErrorList|Error|null
    {
        $authorizer = $this->authorizerContainer->authorizerFor($type);
        $passes = $authorizer->show($request, $model);

        if ($passes === false) {
            return $this->failed($authorizer);
        }

        return null;
    }

    /**
     * @param Authorizer $authorizer
     * @return ErrorList|Error
     * @throws Throwable
     */
    private function failed(Authorizer $authorizer): ErrorList|Error
    {
        $exceptionOrErrors = $authorizer->failed();

        if ($exceptionOrErrors instanceof Throwable) {
            throw $exceptionOrErrors;
        }

        return $exceptionOrErrors;
    }
}
