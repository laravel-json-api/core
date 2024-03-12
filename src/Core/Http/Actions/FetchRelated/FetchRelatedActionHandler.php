<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Http\Actions\FetchRelated;

use LaravelJsonApi\Contracts\Bus\Queries\Dispatcher;
use LaravelJsonApi\Core\Bus\Queries\FetchRelated\FetchRelatedQuery;
use LaravelJsonApi\Core\Bus\Queries\Result;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Core\Http\Actions\Middleware\ItAcceptsJsonApiResponses;
use LaravelJsonApi\Core\Responses\RelatedResponse;
use LaravelJsonApi\Core\Support\PipelineFactory;
use RuntimeException;
use UnexpectedValueException;

class FetchRelatedActionHandler
{
    /**
     * FetchRelatedActionHandler constructor
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
     * Execute the fetch related action.
     *
     * @param FetchRelatedActionInput $action
     * @return RelatedResponse
     */
    public function execute(FetchRelatedActionInput $action): RelatedResponse
    {
        $pipes = [
            ItAcceptsJsonApiResponses::class,
        ];

        $response = $this->pipelines
            ->pipe($action)
            ->through($pipes)
            ->via('handle')
            ->then(fn (FetchRelatedActionInput $passed): RelatedResponse => $this->handle($passed));

        if ($response instanceof RelatedResponse) {
            return $response;
        }

        throw new UnexpectedValueException('Expecting action pipeline to return a data response.');
    }

    /**
     * Handle the fetch related action.
     *
     * @param FetchRelatedActionInput $action
     * @return RelatedResponse
     * @throws JsonApiException
     */
    private function handle(FetchRelatedActionInput $action): RelatedResponse
    {
        $result = $this->query($action);
        $payload = $result->payload();

        if ($payload->hasData === false) {
            throw new RuntimeException('Expecting query result to have data.');
        }

        return RelatedResponse::make($result->relatesTo(), $result->fieldName(), $payload->data)
            ->withMeta($payload->meta)
            ->withQueryParameters($result->query());
    }

    /**
     * @param FetchRelatedActionInput $action
     * @return Result
     * @throws JsonApiException
     */
    private function query(FetchRelatedActionInput $action): Result
    {
        $query = FetchRelatedQuery::make($action->request(), $action->query())
            ->withModel($action->model())
            ->withHooks($action->hooks());

        $result = $this->dispatcher->dispatch($query);

        if ($result->didSucceed()) {
            return $result;
        }

        throw new JsonApiException($result->errors());
    }
}
