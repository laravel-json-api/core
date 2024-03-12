<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Bus\Queries\FetchMany;

use LaravelJsonApi\Contracts\Store\Store;
use LaravelJsonApi\Core\Bus\Queries\FetchMany\Middleware\AuthorizeFetchManyQuery;
use LaravelJsonApi\Core\Bus\Queries\FetchMany\Middleware\TriggerIndexHooks;
use LaravelJsonApi\Core\Bus\Queries\FetchMany\Middleware\ValidateFetchManyQuery;
use LaravelJsonApi\Core\Bus\Queries\Result;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result as Payload;
use LaravelJsonApi\Core\Support\PipelineFactory;
use UnexpectedValueException;

class FetchManyQueryHandler
{
    /**
     * FetchManyQueryHandler constructor
     *
     * @param PipelineFactory $pipelines
     * @param Store $store
     */
    public function __construct(
        private readonly PipelineFactory $pipelines,
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

        $result = $this->pipelines
            ->pipe($query)
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
