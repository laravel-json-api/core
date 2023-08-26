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

namespace LaravelJsonApi\Core\Http\Actions\UpdateRelationship;

use LaravelJsonApi\Contracts\Bus\Commands\Dispatcher as CommandDispatcher;
use LaravelJsonApi\Contracts\Bus\Queries\Dispatcher as QueryDispatcher;
use LaravelJsonApi\Core\Bus\Commands\UpdateRelationship\UpdateRelationshipCommand;
use LaravelJsonApi\Core\Bus\Queries\FetchRelationship\FetchRelationshipQuery;
use LaravelJsonApi\Core\Bus\Queries\Result;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result as Payload;
use LaravelJsonApi\Core\Http\Actions\Middleware\CheckRelationshipJsonIsCompliant;
use LaravelJsonApi\Core\Http\Actions\Middleware\ItAcceptsJsonApiResponses;
use LaravelJsonApi\Core\Http\Actions\Middleware\ItHasJsonApiContent;
use LaravelJsonApi\Core\Http\Actions\Middleware\LookupModelIfMissing;
use LaravelJsonApi\Core\Http\Actions\Middleware\ValidateRelationshipQueryParameters;
use LaravelJsonApi\Core\Http\Actions\UpdateRelationship\Middleware\AuthorizeUpdateRelationshipAction;
use LaravelJsonApi\Core\Http\Actions\UpdateRelationship\Middleware\ParseUpdateRelationshipOperation;
use LaravelJsonApi\Core\Responses\RelationshipResponse;
use LaravelJsonApi\Core\Support\PipelineFactory;
use UnexpectedValueException;

class UpdateRelationshipActionHandler
{
    /**
     * UpdateRelationshipActionHandler constructor
     *
     * @param PipelineFactory $pipelines
     * @param CommandDispatcher $commands
     * @param QueryDispatcher $queries
     */
    public function __construct(
        private readonly PipelineFactory $pipelines,
        private readonly CommandDispatcher $commands,
        private readonly QueryDispatcher $queries,
    ) {
    }

    /**
     * Execute a update relationship action.
     *
     * @param UpdateRelationshipActionInput $action
     * @return RelationshipResponse
     */
    public function execute(UpdateRelationshipActionInput $action): RelationshipResponse
    {
        $pipes = [
            ItHasJsonApiContent::class,
            ItAcceptsJsonApiResponses::class,
            LookupModelIfMissing::class,
            AuthorizeUpdateRelationshipAction::class,
            CheckRelationshipJsonIsCompliant::class,
            ValidateRelationshipQueryParameters::class,
            ParseUpdateRelationshipOperation::class,
        ];

        $response = $this->pipelines
            ->pipe($action)
            ->through($pipes)
            ->via('handle')
            ->then(fn(UpdateRelationshipActionInput $passed): RelationshipResponse => $this->handle($passed));

        if ($response instanceof RelationshipResponse) {
            return $response;
        }

        throw new UnexpectedValueException('Expecting action pipeline to return a data response.');
    }

    /**
     * Handle the update relationship action.
     *
     * @param UpdateRelationshipActionInput $action
     * @return RelationshipResponse
     * @throws JsonApiException
     */
    private function handle(UpdateRelationshipActionInput $action): RelationshipResponse
    {
        $commandResult = $this->dispatch($action);
        $model = $action->modelOrFail();
        $queryResult = $this->query($action, $model);
        $payload = $queryResult->payload();

        assert($payload->hasData, 'Expecting query result to have data.');

        return RelationshipResponse::make($model, $action->fieldName(), $payload->data)
            ->withMeta(array_merge($commandResult->meta, $payload->meta))
            ->withQueryParameters($queryResult->query());
    }

    /**
     * Dispatch the update relationship command.
     *
     * @param UpdateRelationshipActionInput $action
     * @return Payload
     * @throws JsonApiException
     */
    private function dispatch(UpdateRelationshipActionInput $action): Payload
    {
        $command = UpdateRelationshipCommand::make($action->request(), $action->operation())
            ->withModel($action->modelOrFail())
            ->withQuery($action->query())
            ->withHooks($action->hooks())
            ->skipAuthorization();

        $result = $this->commands->dispatch($command);

        if ($result->didSucceed()) {
            return $result->payload();
        }

        throw new JsonApiException($result->errors());
    }

    /**
     * Execute the query for the update relationship action.
     *
     * @param UpdateRelationshipActionInput $action
     * @param object $model
     * @return Result
     * @throws JsonApiException
     */
    private function query(UpdateRelationshipActionInput $action, object $model): Result
    {
        $query = new FetchRelationshipQuery(
            $action->request(),
            $action->type(),
            $action->id(),
            $action->fieldName(),
        );

        $query = $query
            ->withModel($model)
            ->withValidated($action->query())
            ->skipAuthorization();

        $result = $this->queries->dispatch($query);

        if ($result->didSucceed()) {
            return $result;
        }

        throw new JsonApiException($result->errors());
    }
}
