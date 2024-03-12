<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Bus\Queries\FetchRelated;

use LaravelJsonApi\Contracts\Schema\Container;
use LaravelJsonApi\Contracts\Store\Store;
use LaravelJsonApi\Core\Bus\Queries\FetchRelated\Middleware\AuthorizeFetchRelatedQuery;
use LaravelJsonApi\Core\Bus\Queries\FetchRelated\Middleware\TriggerShowRelatedHooks;
use LaravelJsonApi\Core\Bus\Queries\FetchRelated\Middleware\ValidateFetchRelatedQuery;
use LaravelJsonApi\Core\Bus\Queries\Middleware\SetModelIfMissing;
use LaravelJsonApi\Core\Bus\Queries\Result;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result as Payload;
use LaravelJsonApi\Core\Support\PipelineFactory;
use UnexpectedValueException;

class FetchRelatedQueryHandler
{
    /**
     * FetchRelatedQueryHandler constructor
     *
     * @param PipelineFactory $pipelines
     * @param Store $store
     * @param Container $schemas
     */
    public function __construct(
        private readonly PipelineFactory $pipelines,
        private readonly Store $store,
        private readonly Container $schemas,
    ) {
    }

    /**
     * Execute a "fetch related" query.
     *
     * @param FetchRelatedQuery $query
     * @return Result
     */
    public function execute(FetchRelatedQuery $query): Result
    {
        $pipes = [
            SetModelIfMissing::class,
            AuthorizeFetchRelatedQuery::class,
            ValidateFetchRelatedQuery::class,
            TriggerShowRelatedHooks::class,
        ];

        $result = $this->pipelines
            ->pipe($query)
            ->through($pipes)
            ->via('handle')
            ->then(fn (FetchRelatedQuery $q): Result => $this->handle($q));

        if ($result instanceof Result) {
            return $result;
        }

        throw new UnexpectedValueException('Expecting pipeline to return a query result.');
    }

    /**
     * Handle the query.
     *
     * @param FetchRelatedQuery $query
     * @return Result
     */
    private function handle(FetchRelatedQuery $query): Result
    {
        $relation = $this->schemas
            ->schemaFor($type = $query->type())
            ->relationship($fieldName = $query->fieldName());

        $id = $query->id();
        $params = $query->toQueryParams();
        $model = $query->modelOrFail();

        if ($relation->toOne()) {
            $related = $this->store
                ->queryToOne($type, $id, $fieldName)
                ->withQuery($params)
                ->first();
        } else {
            $related = $this->store
                ->queryToMany($type, $id, $fieldName)
                ->withQuery($params)
                ->getOrPaginate($params->page());
        }

        return Result::ok(new Payload($related, true), $params)
            ->withRelatedTo($model, $fieldName);
    }
}
