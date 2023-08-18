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

namespace LaravelJsonApi\Core\Http\Actions\Destroy;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Support\Responsable;
use LaravelJsonApi\Contracts\Bus\Commands\Dispatcher as CommandDispatcher;
use LaravelJsonApi\Core\Bus\Commands\Destroy\DestroyCommand;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result as Payload;
use LaravelJsonApi\Core\Http\Actions\Destroy\Middleware\ParseDeleteOperation;
use LaravelJsonApi\Core\Http\Actions\Middleware\ItAcceptsJsonApiResponses;
use LaravelJsonApi\Core\Responses\MetaResponse;
use LaravelJsonApi\Core\Support\PipelineFactory;
use Symfony\Component\HttpFoundation\Response;
use UnexpectedValueException;

class DestroyActionHandler
{
    /**
     * DestroyActionHandler constructor
     *
     * @param PipelineFactory $pipelines
     * @param CommandDispatcher $commands
     * @param ResponseFactory $responseFactory
     */
    public function __construct(
        private readonly PipelineFactory $pipelines,
        private readonly CommandDispatcher $commands,
        private readonly ResponseFactory $responseFactory,
    ) {
    }

    /**
     * Execute a update action.
     *
     * @param DestroyActionInput $action
     * @return Responsable|Response
     */
    public function execute(DestroyActionInput $action): Responsable|Response
    {
        $pipes = [
            ItAcceptsJsonApiResponses::class,
            ParseDeleteOperation::class,
        ];

        $response = $this->pipelines
            ->pipe($action)
            ->through($pipes)
            ->via('handle')
            ->then(fn(DestroyActionInput $passed): Responsable|Response => $this->handle($passed));

        if ($response instanceof Responsable || $response instanceof Response) {
            return $response;
        }

        throw new UnexpectedValueException('Expecting action pipeline to return a response.');
    }

    /**
     * Handle the destroy action.
     *
     * @param DestroyActionInput $action
     * @return Responsable|Response
     * @throws JsonApiException
     */
    private function handle(DestroyActionInput $action): Responsable|Response
    {
        $payload = $this->dispatch($action);

        assert($payload->hasData === false, 'Expecting command result to not have data.');

        if (!empty($payload->meta)) {
            return new MetaResponse($payload->meta);
        }

        return $this->responseFactory->noContent();
    }

    /**
     * Dispatch the destroy command.
     *
     * @param DestroyActionInput $action
     * @return Payload
     * @throws JsonApiException
     */
    private function dispatch(DestroyActionInput $action): Payload
    {
        $command = DestroyCommand::make($action->request(), $action->operation())
            ->withModel($action->model())
            ->withHooks($action->hooks());

        $result = $this->commands->dispatch($command);

        if ($result->didSucceed()) {
            return $result->payload();
        }

        throw new JsonApiException($result->errors());
    }
}
