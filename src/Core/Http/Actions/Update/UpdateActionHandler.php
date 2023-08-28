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

namespace LaravelJsonApi\Core\Http\Actions\Update;

use LaravelJsonApi\Contracts\Bus\Commands\Dispatcher as CommandDispatcher;
use LaravelJsonApi\Contracts\Bus\Queries\Dispatcher as QueryDispatcher;
use LaravelJsonApi\Core\Bus\Commands\Update\UpdateCommand;
use LaravelJsonApi\Core\Bus\Queries\FetchOne\FetchOneQuery;
use LaravelJsonApi\Core\Bus\Queries\Result;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result as Payload;
use LaravelJsonApi\Core\Http\Actions\Middleware\ItAcceptsJsonApiResponses;
use LaravelJsonApi\Core\Http\Actions\Middleware\ItHasJsonApiContent;
use LaravelJsonApi\Core\Http\Actions\Middleware\LookupModelIfMissing;
use LaravelJsonApi\Core\Http\Actions\Middleware\ValidateQueryOneParameters;
use LaravelJsonApi\Core\Http\Actions\Update\Middleware\AuthorizeUpdateAction;
use LaravelJsonApi\Core\Http\Actions\Update\Middleware\CheckRequestJsonIsCompliant;
use LaravelJsonApi\Core\Http\Actions\Update\Middleware\ParseUpdateOperation;
use LaravelJsonApi\Core\Responses\DataResponse;
use LaravelJsonApi\Core\Support\PipelineFactory;
use UnexpectedValueException;

class UpdateActionHandler
{
    /**
     * UpdateActionHandler constructor
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
     * Execute a update action.
     *
     * @param UpdateActionInput $action
     * @return DataResponse
     */
    public function execute(UpdateActionInput $action): DataResponse
    {
        $pipes = [
            ItHasJsonApiContent::class,
            ItAcceptsJsonApiResponses::class,
            LookupModelIfMissing::class,
            AuthorizeUpdateAction::class,
            CheckRequestJsonIsCompliant::class,
            ValidateQueryOneParameters::class,
            ParseUpdateOperation::class,
        ];

        $response = $this->pipelines
            ->pipe($action)
            ->through($pipes)
            ->via('handle')
            ->then(fn(UpdateActionInput $passed): DataResponse => $this->handle($passed));

        if ($response instanceof DataResponse) {
            return $response;
        }

        throw new UnexpectedValueException('Expecting action pipeline to return a data response.');
    }

    /**
     * Handle the update action.
     *
     * @param UpdateActionInput $action
     * @return DataResponse
     * @throws JsonApiException
     */
    private function handle(UpdateActionInput $action): DataResponse
    {
        $commandResult = $this->dispatch($action);
        $model = $commandResult->data ?? $action->modelOrFail();
        $queryResult = $this->query($action, $model);
        $payload = $queryResult->payload();

        assert($payload->hasData, 'Expecting query result to have data.');

        return DataResponse::make($payload->data)
            ->withMeta(array_merge($commandResult->meta, $payload->meta))
            ->withQueryParameters($queryResult->query())
            ->didntCreate();
    }

    /**
     * Dispatch the update command.
     *
     * @param UpdateActionInput $action
     * @return Payload
     * @throws JsonApiException
     */
    private function dispatch(UpdateActionInput $action): Payload
    {
        $command = UpdateCommand::make($action->request(), $action->operation())
            ->withModel($action->modelOrFail())
            ->withQuery($action->queryParameters())
            ->withHooks($action->hooks())
            ->skipAuthorization();

        $result = $this->commands->dispatch($command);

        if ($result->didSucceed()) {
            return $result->payload();
        }

        throw new JsonApiException($result->errors());
    }

    /**
     * Execute the query for the update action.
     *
     * @param UpdateActionInput $action
     * @param object $model
     * @return Result
     * @throws JsonApiException
     */
    private function query(UpdateActionInput $action, object $model): Result
    {
        $query = FetchOneQuery::make($action->request(), $action->query())
            ->withModel($model)
            ->withValidated($action->queryParameters())
            ->skipAuthorization();

        $result = $this->queries->dispatch($query);

        if ($result->didSucceed()) {
            return $result;
        }

        throw new JsonApiException($result->errors());
    }
}
