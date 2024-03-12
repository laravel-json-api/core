<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
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
            ->validatorsFor($relation->inverse())
            ->withRequest($action->request());

        $query = $action->query();

        $validator = $relation->toOne() ?
            $factory->queryOne()->make($query) :
            $factory->queryMany()->make($query);

        if ($validator->fails()) {
            throw new JsonApiException($this->errorFactory->make($validator));
        }

        $action = $action->withQueryParameters(
            QueryParameters::fromArray($validator->validated()),
        );

        return $next($action);
    }
}
