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

namespace LaravelJsonApi\Core\Bus\Commands\Destroy;

use LaravelJsonApi\Contracts\Store\Store;
use LaravelJsonApi\Core\Bus\Commands\Destroy\Middleware\AuthorizeDestroyCommand;
use LaravelJsonApi\Core\Bus\Commands\Destroy\Middleware\TriggerDestroyHooks;
use LaravelJsonApi\Core\Bus\Commands\Destroy\Middleware\ValidateDestroyCommand;
use LaravelJsonApi\Core\Bus\Commands\Middleware\LookupModelIfMissing;
use LaravelJsonApi\Core\Bus\Commands\Result;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result as Payload;
use LaravelJsonApi\Core\Support\PipelineFactory;

class DestroyCommandHandler
{
    /**
     * DestroyCommandHandler constructor
     *
     * @param PipelineFactory $pipelines
     * @param Store $store
     */
    public function __construct(
        private readonly PipelineFactory $pipelines,
        private readonly Store $store,
    ) {
    }

    /**
     * Execute an update command.
     *
     * @param DestroyCommand $command
     * @return Result
     */
    public function execute(DestroyCommand $command): Result
    {
        $pipes = [
            // @TODO only need to load model if authorizing, validating or have hooks to call.
            LookupModelIfMissing::class,
            AuthorizeDestroyCommand::class,
            ValidateDestroyCommand::class,
            TriggerDestroyHooks::class,
        ];

        $result = $this->pipelines
            ->pipe($command)
            ->through($pipes)
            ->via('handle')
            ->then(fn (DestroyCommand $cmd): Result => $this->handle($cmd));

        assert(
            $result instanceof Result,
            'Expecting pipeline to return a command result.',
        );

        return $result;
    }

    /**
     * Handle the command.
     *
     * @param DestroyCommand $command
     * @return Result
     */
    private function handle(DestroyCommand $command): Result
    {
        $this->store->delete(
            $command->type(),
            $command->model() ?? $command->id(),
        );

        return Result::ok(Payload::none());
    }
}
