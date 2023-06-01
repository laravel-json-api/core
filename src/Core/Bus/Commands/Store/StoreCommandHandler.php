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

namespace LaravelJsonApi\Core\Bus\Commands\Store;

use Illuminate\Contracts\Pipeline\Pipeline;
use LaravelJsonApi\Contracts\Store\Store;
use LaravelJsonApi\Core\Bus\Commands\Result;
use LaravelJsonApi\Core\Bus\Commands\Store\Middleware\AuthorizeStoreCommand;
use LaravelJsonApi\Core\Bus\Commands\Store\Middleware\ValidateStoreCommand;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result as Payload;
use UnexpectedValueException;

class StoreCommandHandler
{
    /**
     * StoreCommandHandler constructor
     *
     * @param Pipeline $pipeline
     * @param Store $store
     */
    public function __construct(
        private readonly Pipeline $pipeline,
        private readonly Store $store,
    ) {
    }

    /**
     * Execute a store command.
     *
     * @param StoreCommand $command
     * @return Result
     */
    public function execute(StoreCommand $command): Result
    {
        $pipes = [
            AuthorizeStoreCommand::class,
            ValidateStoreCommand::class,
        ];

        $result = $this->pipeline
            ->send($command)
            ->through($pipes)
            ->via('handle')
            ->then(fn (StoreCommand $cmd): Result => $this->handle($cmd));

        if ($result instanceof Result) {
            return $result;
        }

        throw new UnexpectedValueException('Expecting pipeline to return a command result.');
    }

    /**
     * Handle the command.
     *
     * @param StoreCommand $command
     * @return Result
     */
    private function handle(StoreCommand $command): Result
    {
        $resource = $this->store
            ->create($command->type()->value)
            ->maybeWithRequest($command->request())
            ->store($command->validated());

        return Result::ok(new Payload($resource, true));
    }
}
