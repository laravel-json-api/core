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

use Illuminate\Contracts\Pipeline\Pipeline;
use LaravelJsonApi\Contracts\Bus\Commands\Dispatcher as CommandDispatcher;
use LaravelJsonApi\Contracts\Bus\Queries\Dispatcher as QueryDispatcher;
use LaravelJsonApi\Core\Bus\Commands\Store\StoreCommand;
use LaravelJsonApi\Core\Bus\Queries\FetchOne\FetchOneQuery;
use LaravelJsonApi\Core\Bus\Queries\Result;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result as Payload;
use LaravelJsonApi\Core\Http\Actions\Store\Middleware\CheckRequestJsonIsCompliant;
use LaravelJsonApi\Core\Http\Actions\Store\Middleware\ParseStoreOperation;
use LaravelJsonApi\Core\Http\Actions\Store\Middleware\ValidateQueryParameters;
use LaravelJsonApi\Core\Responses\DataResponse;
use RuntimeException;
use UnexpectedValueException;

class StoreActionHandler
{
    /**
     * StoreActionHandler constructor
     *
     * @param Pipeline $pipeline
     * @param CommandDispatcher $commands
     * @param QueryDispatcher $queries
     */
    public function __construct(
        private readonly Pipeline $pipeline,
        private readonly CommandDispatcher $commands,
        private readonly QueryDispatcher $queries,
    ) {
    }

    /**
     * Execute a store action.
     *
     * @param StoreAction $action
     * @return DataResponse
     */
    public function execute(StoreAction $action): DataResponse
    {
        $pipes = [
            CheckRequestJsonIsCompliant::class,
            ValidateQueryParameters::class,
            ParseStoreOperation::class,
        ];

        $response = $this->pipeline
            ->send($action)
            ->through($pipes)
            ->via('handle')
            ->then(fn(StoreAction $passed): DataResponse => $this->handle($passed));

        if ($response instanceof DataResponse) {
            return $response;
        }

        throw new UnexpectedValueException('Expecting action pipeline to return a data response.');
    }

    /**
     * Handle the store action.
     *
     * @param StoreAction $action
     * @return DataResponse
     * @throws JsonApiException
     */
    private function handle(StoreAction $action): DataResponse
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
     * @param StoreAction $action
     * @return Payload
     * @throws JsonApiException
     */
    private function dispatch(StoreAction $action): Payload
    {
        $command = StoreCommand::make($action->request(), $action->operation())
            ->withQuery($action->query())
            ->withHooks($action->hooks());

        $result = $this->commands->dispatch($command);

        if ($result->didSucceed()) {
            return $result->payload();
        }

        throw new JsonApiException($result->errors());
    }

    /**
     * Execute the query for the store action.
     *
     * @param StoreAction $action
     * @param object $model
     * @return Result
     * @throws JsonApiException
     */
    private function query(StoreAction $action, object $model): Result
    {
        $query = FetchOneQuery::make($action->request(), $action->type())
            ->withModel($model)
            ->skipAuthorization()
            ->withValidated($action->query())
            ->withHooks($action->hooks());

        $result = $this->queries->dispatch($query);

        if ($result->didSucceed()) {
            return $result;
        }

        throw new JsonApiException($result->errors());
    }
}
