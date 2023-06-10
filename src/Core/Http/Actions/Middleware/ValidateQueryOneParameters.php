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

namespace LaravelJsonApi\Core\Http\Actions\Middleware;

use Closure;
use LaravelJsonApi\Contracts\Validation\Container as ValidatorContainer;
use LaravelJsonApi\Contracts\Validation\QueryErrorFactory;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Core\Http\Actions\ActionInput;
use LaravelJsonApi\Core\Query\QueryParameters;
use LaravelJsonApi\Core\Responses\DataResponse;

class ValidateQueryOneParameters implements HandlesActions
{
    /**
     * ValidateQueryParameters constructor
     *
     * @param ValidatorContainer $validatorContainer
     * @param QueryErrorFactory $errorFactory
     */
    public function __construct(
        private readonly ValidatorContainer $validatorContainer,
        private readonly QueryErrorFactory  $errorFactory,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function handle(ActionInput $action, Closure $next): DataResponse
    {
        $validator = $this->validatorContainer
            ->validatorsFor($action->type())
            ->queryOne()
            ->forRequest($action->request());

        if ($validator->fails()) {
            throw new JsonApiException($this->errorFactory->make($validator));
        }

        $action = $action->withQuery(
            QueryParameters::fromArray($validator->validated()),
        );

        return $next($action);
    }
}
