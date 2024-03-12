<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Bus\Commands\Store;

use LaravelJsonApi\Contracts\Store\Store;
use LaravelJsonApi\Core\Bus\Commands\Result;
use LaravelJsonApi\Core\Bus\Commands\Store\Middleware\AuthorizeStoreCommand;
use LaravelJsonApi\Core\Bus\Commands\Store\Middleware\TriggerStoreHooks;
use LaravelJsonApi\Core\Bus\Commands\Store\Middleware\ValidateStoreCommand;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result as Payload;
use LaravelJsonApi\Core\Support\PipelineFactory;
use UnexpectedValueException;

class StoreCommandHandler
{
    /**
     * StoreCommandHandler constructor
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
            TriggerStoreHooks::class,
        ];

        $result = $this->pipelines
            ->pipe($command)
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
        $model = $this->store
            ->create($command->type())
            ->withRequest($command->request())
            ->store($command->safe());

        return Result::ok(new Payload($model, true));
    }
}
