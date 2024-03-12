<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Http\Actions\FetchRelationship;

use LaravelJsonApi\Contracts\Bus\Queries\Dispatcher;
use LaravelJsonApi\Core\Bus\Queries\FetchRelationship\FetchRelationshipQuery;
use LaravelJsonApi\Core\Bus\Queries\Result;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Core\Http\Actions\Middleware\ItAcceptsJsonApiResponses;
use LaravelJsonApi\Core\Responses\RelationshipResponse;
use LaravelJsonApi\Core\Support\PipelineFactory;
use RuntimeException;
use UnexpectedValueException;

class FetchRelationshipActionHandler
{
    /**
     * FetchRelationshipActionHandler constructor
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
     * Execute the fetch relationship action.
     *
     * @param FetchRelationshipActionInput $action
     * @return RelationshipResponse
     */
    public function execute(FetchRelationshipActionInput $action): RelationshipResponse
    {
        $pipes = [
            ItAcceptsJsonApiResponses::class,
        ];

        $response = $this->pipelines
            ->pipe($action)
            ->through($pipes)
            ->via('handle')
            ->then(fn (FetchRelationshipActionInput $passed): RelationshipResponse => $this->handle($passed));

        if ($response instanceof RelationshipResponse) {
            return $response;
        }

        throw new UnexpectedValueException('Expecting action pipeline to return a data response.');
    }

    /**
     * Handle the fetch related action.
     *
     * @param FetchRelationshipActionInput $action
     * @return RelationshipResponse
     * @throws JsonApiException
     */
    private function handle(FetchRelationshipActionInput $action): RelationshipResponse
    {
        $result = $this->query($action);
        $payload = $result->payload();

        if ($payload->hasData === false) {
            throw new RuntimeException('Expecting query result to have data.');
        }

        return RelationshipResponse::make($result->relatesTo(), $result->fieldName(), $payload->data)
            ->withMeta($payload->meta)
            ->withQueryParameters($result->query());
    }

    /**
     * @param FetchRelationshipActionInput $action
     * @return Result
     * @throws JsonApiException
     */
    private function query(FetchRelationshipActionInput $action): Result
    {
        $query = FetchRelationshipQuery::make($action->request(), $action->query())
            ->withModel($action->model())
            ->withHooks($action->hooks());

        $result = $this->dispatcher->dispatch($query);

        if ($result->didSucceed()) {
            return $result;
        }

        throw new JsonApiException($result->errors());
    }
}
