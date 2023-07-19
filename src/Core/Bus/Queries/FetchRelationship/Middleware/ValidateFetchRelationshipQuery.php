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

namespace LaravelJsonApi\Core\Bus\Queries\FetchRelationship\Middleware;

use Closure;
use Illuminate\Contracts\Validation\Validator;
use LaravelJsonApi\Contracts\Schema\Container as SchemaContainer;
use LaravelJsonApi\Contracts\Validation\Container as ValidatorContainer;
use LaravelJsonApi\Contracts\Validation\QueryErrorFactory;
use LaravelJsonApi\Core\Bus\Queries\FetchRelationship\FetchRelationshipQuery;
use LaravelJsonApi\Core\Bus\Queries\FetchRelationship\HandlesFetchRelationshipQueries;
use LaravelJsonApi\Core\Bus\Queries\Result;

class ValidateFetchRelationshipQuery implements HandlesFetchRelationshipQueries
{
    /**
     * ValidateFetchRelatedQuery constructor
     *
     * @param SchemaContainer $schemaContainer
     * @param ValidatorContainer $validatorContainer
     * @param QueryErrorFactory $errorFactory
     */
    public function __construct(
        private readonly SchemaContainer $schemaContainer,
        private readonly ValidatorContainer $validatorContainer,
        private readonly QueryErrorFactory $errorFactory,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function handle(FetchRelationshipQuery $query, Closure $next): Result
    {
        if ($query->mustValidate()) {
            $validator = $this->validatorFor($query);

            if ($validator->fails()) {
                return Result::failed(
                    $this->errorFactory->make($validator),
                );
            }

            $query = $query->withValidated(
                $validator->validated(),
            );
        }

        if ($query->isNotValidated()) {
            $query = $query->withValidated(
                $query->parameters(),
            );
        }

        return $next($query);
    }

    /**
     * @param FetchRelationshipQuery $query
     * @return Validator
     */
    private function validatorFor(FetchRelationshipQuery $query): Validator
    {
        $relation = $this->schemaContainer
            ->schemaFor($query->type())
            ->relationship($query->fieldName());

        $factory = $this->validatorContainer
            ->validatorsFor($relation->inverse());

        $request = $query->request();
        $params = $query->parameters();

        return $relation->toOne() ?
            $factory->queryOne()->make($request, $params) :
            $factory->queryMany()->make($request, $params);
    }
}
