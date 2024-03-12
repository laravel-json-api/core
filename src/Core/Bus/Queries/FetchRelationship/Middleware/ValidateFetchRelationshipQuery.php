<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
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
                $query->input()->parameters,
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
            ->validatorsFor($relation->inverse())
            ->withRequest($query->request());

        $input = $query->input();

        return $relation->toOne() ?
            $factory->queryOne()->make($input) :
            $factory->queryMany()->make($input);
    }
}
