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

namespace LaravelJsonApi\Core\Http\Actions\Store;

use LaravelJsonApi\Contracts\Bus\Commands\Dispatcher as CommandDispatcher;
use LaravelJsonApi\Contracts\Bus\Queries\Dispatcher as QueryDispatcher;
use LaravelJsonApi\Contracts\Resources\Container;
use LaravelJsonApi\Core\Bus\Commands\Store\StoreCommand;
use LaravelJsonApi\Core\Bus\Queries\FetchOne\FetchOneQuery;
use LaravelJsonApi\Core\Bus\Queries\Result;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result as Payload;
use LaravelJsonApi\Core\Http\Actions\Middleware\ItAcceptsJsonApiResponses;
use LaravelJsonApi\Core\Http\Actions\Middleware\ItHasJsonApiContent;
use LaravelJsonApi\Core\Http\Actions\Middleware\ValidateQueryOneParameters;
use LaravelJsonApi\Core\Http\Actions\Store\Middleware\AuthorizeStoreAction;
use LaravelJsonApi\Core\Http\Actions\Store\Middleware\CheckRequestJsonIsCompliant;
use LaravelJsonApi\Core\Http\Actions\Store\Middleware\ParseStoreOperation;
use LaravelJsonApi\Core\Responses\DataResponse;
use LaravelJsonApi\Core\Support\PipelineFactory;
use RuntimeException;
use UnexpectedValueException;

class StoreActionHandler
{
    /**
     * StoreActionHandler constructor
     *
     * @param PipelineFactory $pipelines
     * @param CommandDispatcher $commands
     * @param QueryDispatcher $queries
     * @param Container $resources
     */
    public function __construct(
        private readonly PipelineFactory $pipelines,
        private readonly CommandDispatcher $commands,
        private readonly QueryDispatcher $queries,
        private readonly Container $resources,
    ) {
    }

    /**
     * Execute a store action.
     *
     * @param StoreActionInput $action
     * @return DataResponse
     */
    public function execute(StoreActionInput $action): DataResponse
    {
        $pipes = [
            ItHasJsonApiContent::class,
            ItAcceptsJsonApiResponses::class,
            AuthorizeStoreAction::class,
            CheckRequestJsonIsCompliant::class,
            ValidateQueryOneParameters::class,
            ParseStoreOperation::class,
        ];

        $response = $this->pipelines
            ->pipe($action)
            ->through($pipes)
            ->via('handle')
            ->then(fn(StoreActionInput $passed): DataResponse => $this->handle($passed));

        if ($response instanceof DataResponse) {
            return $response;
        }

        throw new UnexpectedValueException('Expecting action pipeline to return a data response.');
    }

    /**
     * Handle the store action.
     *
     * @param StoreActionInput $action
     * @return DataResponse
     * @throws JsonApiException
     */
    private function handle(StoreActionInput $action): DataResponse
    {
        $command = $this->dispatch($action);

        if ($command->hasData === false || !is_object($command->data)) {
            throw new RuntimeException('Expecting command result to have an object as data.');
        }

        $result = $this->query($action, $command->data);
        $payload = $result->payload();

        if ($payload->hasData === false) {
            throw new RuntimeException('Expecting query result to have data.');
        }

        return DataResponse::make($payload->data)
            ->withMeta(array_merge($command->meta, $payload->meta))
            ->withQueryParameters($result->query())
            ->didCreate();
    }

    /**
     * Dispatch the store command.
     *
     * @param StoreActionInput $action
     * @return Payload
     * @throws JsonApiException
     */
    private function dispatch(StoreActionInput $action): Payload
    {
        $command = StoreCommand::make($action->request(), $action->operation())
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
     * Execute the query for the store action.
     *
     * @param StoreActionInput $action
     * @param object $model
     * @return Result
     * @throws JsonApiException
     */
    private function query(StoreActionInput $action, object $model): Result
    {
        $id = $this->resources->idForType(
            $action->type(),
            $model,
        );

        $query = FetchOneQuery::make($action->request(), $action->query()->withId($id))
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
