<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Bus\Queries\FetchOne\Middleware;

use Closure;
use LaravelJsonApi\Contracts\Validation\Container as ValidatorContainer;
use LaravelJsonApi\Contracts\Validation\QueryErrorFactory;
use LaravelJsonApi\Core\Bus\Queries\FetchOne\FetchOneQuery;
use LaravelJsonApi\Core\Bus\Queries\FetchOne\HandlesFetchOneQueries;
use LaravelJsonApi\Core\Bus\Queries\Result;

class ValidateFetchOneQuery implements HandlesFetchOneQueries
{
    /**
     * ValidateFetchOneQuery constructor
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
    public function handle(FetchOneQuery $query, Closure $next): Result
    {
        if ($query->mustValidate()) {
            $validator = $this->validatorContainer
                ->validatorsFor($query->type())
                ->withRequest($query->request())
                ->queryOne()
                ->make($query->input());

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
}
