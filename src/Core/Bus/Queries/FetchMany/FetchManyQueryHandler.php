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

namespace LaravelJsonApi\Core\Bus\Queries\FetchMany;

use Illuminate\Contracts\Pipeline\Pipeline;
use LaravelJsonApi\Contracts\Store\Store;
use LaravelJsonApi\Core\Bus\Queries\FetchMany\Middleware\AuthorizeFetchManyQuery;
use LaravelJsonApi\Core\Bus\Queries\FetchMany\Middleware\TriggerIndexHooks;
use LaravelJsonApi\Core\Bus\Queries\FetchMany\Middleware\ValidateFetchManyQuery;
use LaravelJsonApi\Core\Bus\Queries\Result;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result as Payload;
use UnexpectedValueException;

class FetchManyQueryHandler
{
    /**
     * FetchManyQueryHandler constructor
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
     * Execute a "fetch many" query.
     *
     * @param FetchManyQuery $query
     * @return Result
     */
    public function execute(FetchManyQuery $query): Result
    {
        $pipes = [
            AuthorizeFetchManyQuery::class,
            ValidateFetchManyQuery::class,
            TriggerIndexHooks::class,
        ];

        $result = $this->pipeline
            ->send($query)
            ->through($pipes)
            ->via('handle')
            ->then(fn (FetchManyQuery $q): Result => $this->handle($q));

        if ($result instanceof Result) {
            return $result;
        }

        throw new UnexpectedValueException('Expecting pipeline to return a query result.');
    }

    /**
     * @param FetchManyQuery $query
     * @return Result
     */
    private function handle(FetchManyQuery $query): Result
    {
        $params = $query->toQueryParams();

        $modelOrModels = $this->store
            ->queryAll($query->type())
            ->withQuery($params)
            ->firstOrPaginate($params->page());

        return Result::ok(
            new Payload($modelOrModels, true),
            $params,
        );
    }
}
