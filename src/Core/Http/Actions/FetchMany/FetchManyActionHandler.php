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

namespace LaravelJsonApi\Core\Http\Actions\FetchMany;

use Illuminate\Contracts\Pipeline\Pipeline;
use LaravelJsonApi\Contracts\Bus\Queries\Dispatcher;
use LaravelJsonApi\Core\Bus\Queries\FetchMany\FetchManyQuery;
use LaravelJsonApi\Core\Bus\Queries\Result;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Core\Http\Actions\Middleware\ItAcceptsJsonApiResponses;
use LaravelJsonApi\Core\Responses\DataResponse;
use RuntimeException;
use UnexpectedValueException;

class FetchManyActionHandler
{
    /**
     * FetchManyActionHandler constructor
     *
     * @param Pipeline $pipeline
     * @param Dispatcher $dispatcher
     */
    public function __construct(
        private readonly Pipeline $pipeline,
        private readonly Dispatcher $dispatcher
    ) {
    }

    /**
     * Execute the fetch many action.
     *
     * @param FetchManyActionInput $action
     * @return DataResponse
     */
    public function execute(FetchManyActionInput $action): DataResponse
    {
        $pipes = [
            ItAcceptsJsonApiResponses::class,
        ];

        $response = $this->pipeline
            ->send($action)
            ->through($pipes)
            ->via('handle')
            ->then(fn (FetchManyActionInput $passed): DataResponse => $this->handle($passed));

        if ($response instanceof DataResponse) {
            return $response;
        }

        throw new UnexpectedValueException('Expecting action pipeline to return a data response.');
    }

    /**
     * Handle the fetch one action.
     *
     * @param FetchManyActionInput $action
     * @return DataResponse
     * @throws JsonApiException
     */
    private function handle(FetchManyActionInput $action): DataResponse
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
     * @param FetchManyActionInput $action
     * @return Result
     * @throws JsonApiException
     */
    private function query(FetchManyActionInput $action): Result
    {
        $query = FetchManyQuery::make($action->request(), $action->type())
            ->withHooks($action->hooks());

        $result = $this->dispatcher->dispatch($query);

        if ($result->didSucceed()) {
            return $result;
        }

        throw new JsonApiException($result->errors());
    }
}
