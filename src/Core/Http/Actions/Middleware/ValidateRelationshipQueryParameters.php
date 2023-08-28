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
use Illuminate\Contracts\Support\Responsable;
use LaravelJsonApi\Contracts\Schema\Container as SchemaContainer;
use LaravelJsonApi\Contracts\Validation\Container as ValidatorContainer;
use LaravelJsonApi\Contracts\Validation\QueryErrorFactory;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Core\Http\Actions\Input\ActionInput;
use LaravelJsonApi\Core\Http\Actions\Input\IsRelatable;
use LaravelJsonApi\Core\Query\QueryParameters;

class ValidateRelationshipQueryParameters
{
    /**
     * ValidateRelationshipQueryParameters constructor
     *
     * @param SchemaContainer $schemas
     * @param ValidatorContainer $validators
     * @param QueryErrorFactory $errorFactory
     */
    public function __construct(
        private readonly SchemaContainer $schemas,
        private readonly ValidatorContainer $validators,
        private readonly QueryErrorFactory  $errorFactory,
    ) {
    }

    /**
     * Handle a relatable action.
     *
     * @param ActionInput&IsRelatable $action
     * @param Closure $next
     * @return Responsable
     * @throws JsonApiException
     */
    public function handle(ActionInput&IsRelatable $action, Closure $next): Responsable
    {
        $relation = $this->schemas
            ->schemaFor($action->type())
            ->relationship($action->fieldName());

        $factory = $this->validators
            ->validatorsFor($relation->inverse());

        $request = $action->request();
        $query = $action->query();

        $validator = $relation->toOne() ?
            $factory->queryOne()->make($request, $query) :
            $factory->queryMany()->make($request, $query);

        if ($validator->fails()) {
            throw new JsonApiException($this->errorFactory->make($validator));
        }

        $action = $action->withQueryParameters(
            QueryParameters::fromArray($validator->validated()),
        );

        return $next($action);
    }
}
