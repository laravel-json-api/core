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

namespace LaravelJsonApi\Core\Bus\Commands\Update;

use LaravelJsonApi\Contracts\Store\Store;
use LaravelJsonApi\Core\Bus\Commands\Middleware\LookupModelIfMissing;
use LaravelJsonApi\Core\Bus\Commands\Result;
use LaravelJsonApi\Core\Bus\Commands\Update\Middleware\AuthorizeUpdateCommand;
use LaravelJsonApi\Core\Bus\Commands\Update\Middleware\TriggerUpdateHooks;
use LaravelJsonApi\Core\Bus\Commands\Update\Middleware\ValidateUpdateCommand;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result as Payload;
use LaravelJsonApi\Core\Support\PipelineFactory;
use UnexpectedValueException;

class UpdateCommandHandler
{
    /**
     * UpdateCommandHandler constructor
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
     * @param UpdateCommand $command
     * @return Result
     */
    public function execute(UpdateCommand $command): Result
    {
        $pipes = [
            LookupModelIfMissing::class,
            AuthorizeUpdateCommand::class,
            ValidateUpdateCommand::class,
            TriggerUpdateHooks::class,
        ];

        $result = $this->pipelines
            ->pipe($command)
            ->through($pipes)
            ->via('handle')
            ->then(fn (UpdateCommand $cmd): Result => $this->handle($cmd));

        if ($result instanceof Result) {
            return $result;
        }

        throw new UnexpectedValueException('Expecting pipeline to return a command result.');
    }

    /**
     * Handle the command.
     *
     * @param UpdateCommand $command
     * @return Result
     */
    private function handle(UpdateCommand $command): Result
    {
        $model = $this->store
            ->update($command->type(), $command->modelOrFail())
            ->withRequest($command->request())
            ->store($command->safe());

        return Result::ok(new Payload($model, true));
    }
}
