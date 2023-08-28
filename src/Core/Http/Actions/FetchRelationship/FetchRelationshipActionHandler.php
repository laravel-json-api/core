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
