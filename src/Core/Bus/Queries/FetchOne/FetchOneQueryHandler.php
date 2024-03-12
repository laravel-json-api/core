<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Bus\Queries\FetchOne;

use LaravelJsonApi\Contracts\Store\Store;
use LaravelJsonApi\Core\Bus\Queries\FetchOne\Middleware\AuthorizeFetchOneQuery;
use LaravelJsonApi\Core\Bus\Queries\FetchOne\Middleware\TriggerShowHooks;
use LaravelJsonApi\Core\Bus\Queries\FetchOne\Middleware\ValidateFetchOneQuery;
use LaravelJsonApi\Core\Bus\Queries\Middleware\SetModelIfMissing;
use LaravelJsonApi\Core\Bus\Queries\Result;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result as Payload;
use LaravelJsonApi\Core\Support\PipelineFactory;
use UnexpectedValueException;

class FetchOneQueryHandler
{
    /**
     * FetchOneQueryHandler constructor
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
     * Execute a "fetch one" query.
     *
     * @param FetchOneQuery $query
     * @return Result
     */
    public function execute(FetchOneQuery $query): Result
    {
        $pipes = [
            SetModelIfMissing::class,
            AuthorizeFetchOneQuery::class,
            ValidateFetchOneQuery::class,
            TriggerShowHooks::class,
        ];

        $result = $this->pipelines
            ->pipe($query)
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
            ->queryOne($query->type(), $query->id())
            ->withQuery($params)
            ->first();

        return Result::ok(
            new Payload($model, true),
            $params,
        );
    }
}
