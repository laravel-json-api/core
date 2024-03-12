<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Bus\Commands\Destroy;

use LaravelJsonApi\Contracts\Store\Store;
use LaravelJsonApi\Core\Bus\Commands\Destroy\Middleware\AuthorizeDestroyCommand;
use LaravelJsonApi\Core\Bus\Commands\Destroy\Middleware\TriggerDestroyHooks;
use LaravelJsonApi\Core\Bus\Commands\Destroy\Middleware\ValidateDestroyCommand;
use LaravelJsonApi\Core\Bus\Commands\Middleware\SetModelIfMissing;
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
            SetModelIfMissing::class,
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
