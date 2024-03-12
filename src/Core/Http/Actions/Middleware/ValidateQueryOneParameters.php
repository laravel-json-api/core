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
use LaravelJsonApi\Contracts\Validation\Container as ValidatorContainer;
use LaravelJsonApi\Contracts\Validation\QueryErrorFactory;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Core\Http\Actions\Store\StoreActionInput;
use LaravelJsonApi\Core\Http\Actions\Update\UpdateActionInput;
use LaravelJsonApi\Core\Query\QueryParameters;

class ValidateQueryOneParameters
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
     * @param StoreActionInput|UpdateActionInput $action
     * @param Closure $next
     * @return Responsable
     * @throws JsonApiException
     */
    public function handle(StoreActionInput|UpdateActionInput $action, Closure $next): Responsable
    {
        $validator = $this->validatorContainer
            ->validatorsFor($action->type())
            ->withRequest($action->request())
            ->queryOne()
            ->make($action->query());

        if ($validator->fails()) {
            throw new JsonApiException($this->errorFactory->make($validator));
        }

        $action = $action->withQueryParameters(
            QueryParameters::fromArray($validator->validated()),
        );

        return $next($action);
    }
}
