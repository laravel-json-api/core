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

namespace LaravelJsonApi\Core\Bus\Queries\FetchOne;

use Illuminate\Contracts\Pipeline\Pipeline;
use LaravelJsonApi\Contracts\Store\Store;
use LaravelJsonApi\Core\Bus\Queries\FetchOne\Middleware\AuthorizeFetchOneQuery;
use LaravelJsonApi\Core\Bus\Queries\FetchOne\Middleware\TriggerShowHooks;
use LaravelJsonApi\Core\Bus\Queries\FetchOne\Middleware\ValidateFetchOneQuery;
use LaravelJsonApi\Core\Bus\Queries\Middleware\LookupModelIfAuthorizing;
use LaravelJsonApi\Core\Bus\Queries\Middleware\LookupResourceIdIfNotSet;
use LaravelJsonApi\Core\Bus\Queries\Result;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result as Payload;
use UnexpectedValueException;

class FetchOneQueryHandler
{
    /**
     * FetchOneQueryHandler constructor
     *
     * @param Pipeline $pipeline
     * @param Store $store
     */
    public function __construct(
        private readonly Pipeline $pipeline,
        private readonly Store $store,
    ) {
    }

    /**
     * Execute a "fetch one" query.
     *
     * @param FetchOneQuery $query
     * @return Result
     */
    public function execute(FetchOneQuery $query): Result
    {
        $pipes = [
            LookupModelIfAuthorizing::class,
            AuthorizeFetchOneQuery::class,
            ValidateFetchOneQuery::class,
            LookupResourceIdIfNotSet::class,
            TriggerShowHooks::class,
        ];

        $result = $this->pipeline
            ->send($query)
            ->through($pipes)
            ->via('handle')
            ->then(fn (FetchOneQuery $q): Result => $this->handle($q));

        if ($result instanceof Result) {
            return $result;
        }

        throw new UnexpectedValueException('Expecting pipeline to return a query result.');
    }

    /**
     * Handle the query.
     *
     * @param FetchOneQuery $query
     * @return Result
     */
    private function handle(FetchOneQuery $query): Result
    {
        $params = $query->toQueryParams();

        $model = $this->store
            ->queryOne($query->type(), $query->idOrFail())
            ->withQuery($params)
            ->first();

        return Result::ok(
            new Payload($model, true),
            $params,
        );
    }
}
