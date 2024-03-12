<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Http\Actions\FetchOne;

use LaravelJsonApi\Contracts\Bus\Queries\Dispatcher;
use LaravelJsonApi\Core\Bus\Queries\FetchOne\FetchOneQuery;
use LaravelJsonApi\Core\Bus\Queries\Result;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Core\Http\Actions\Middleware\ItAcceptsJsonApiResponses;
use LaravelJsonApi\Core\Responses\DataResponse;
use LaravelJsonApi\Core\Support\PipelineFactory;
use RuntimeException;
use UnexpectedValueException;

class FetchOneActionHandler
{
    /**
     * FetchOneActionHandler constructor
     *
     * @param PipelineFactory $pipelines
     * @param Dispatcher $dispatcher
     */
    public function __construct(
        private readonly PipelineFactory $pipelines,
        private readonly Dispatcher $dispatcher
    ) {
    }

    /**
     * Execute the fetch one action.
     *
     * @param FetchOneActionInput $action
     * @return DataResponse
     */
    public function execute(FetchOneActionInput $action): DataResponse
    {
        $pipes = [
            ItAcceptsJsonApiResponses::class,
        ];

        $response = $this->pipelines
            ->pipe($action)
            ->through($pipes)
            ->via('handle')
            ->then(fn (FetchOneActionInput $passed): DataResponse => $this->handle($passed));

        if ($response instanceof DataResponse) {
            return $response;
        }

        throw new UnexpectedValueException('Expecting action pipeline to return a data response.');
    }

    /**
     * Handle the fetch one action.
     *
     * @param FetchOneActionInput $action
     * @return DataResponse
     * @throws JsonApiException
     */
    private function handle(FetchOneActionInput $action): DataResponse
    {
        $result = $this->query($action);
        $payload = $result->payload();

        if ($payload->hasData === false) {
            throw new RuntimeException('Expecting query result to have data.');
        }

        return DataResponse::make($payload->data)
            ->withMeta($payload->meta)
            ->withQueryParameters($result->query());
    }

    /**
     * @param FetchOneActionInput $action
     * @return Result
     * @throws JsonApiException
     */
    private function query(FetchOneActionInput $action): Result
    {
        $query = FetchOneQuery::make($action->request(), $action->query())
            ->withModel($action->model())
            ->withHooks($action->hooks());

        $result = $this->dispatcher->dispatch($query);

        if ($result->didSucceed()) {
            return $result;
        }

        throw new JsonApiException($result->errors());
    }
}
