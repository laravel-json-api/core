<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Bus\Commands\DetachRelationship;

use LaravelJsonApi\Contracts\Store\Store;
use LaravelJsonApi\Core\Bus\Commands\DetachRelationship\Middleware\AuthorizeDetachRelationshipCommand;
use LaravelJsonApi\Core\Bus\Commands\DetachRelationship\Middleware\TriggerDetachRelationshipHooks;
use LaravelJsonApi\Core\Bus\Commands\Middleware\SetModelIfMissing;
use LaravelJsonApi\Core\Bus\Commands\Middleware\ValidateRelationshipCommand;
use LaravelJsonApi\Core\Bus\Commands\Result;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result as Payload;
use LaravelJsonApi\Core\Support\Contracts;
use LaravelJsonApi\Core\Support\PipelineFactory;
use UnexpectedValueException;

class DetachRelationshipCommandHandler
{
    /**
     * DetachRelationshipCommandHandler constructor
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
     * Execute an detach relationship command.
     *
     * @param DetachRelationshipCommand $command
     * @return Result
     */
    public function execute(DetachRelationshipCommand $command): Result
    {
        $pipes = [
            SetModelIfMissing::class,
            AuthorizeDetachRelationshipCommand::class,
            ValidateRelationshipCommand::class,
            TriggerDetachRelationshipHooks::class,
        ];

        $result = $this->pipelines
            ->pipe($command)
            ->through($pipes)
            ->via('handle')
            ->then(fn (DetachRelationshipCommand $cmd): Result => $this->handle($cmd));

        if ($result instanceof Result) {
            return $result;
        }

        throw new UnexpectedValueException('Expecting pipeline to return a command result.');
    }

    /**
     * Handle the command.
     *
     * @param DetachRelationshipCommand $command
     * @return Result
     */
    private function handle(DetachRelationshipCommand $command): Result
    {
        $fieldName = $command->fieldName();
        $validated = $command->validated();

        Contracts::assert(
            array_key_exists($fieldName, $validated),
            sprintf('Relation %s must have a validation rule so that it is validated.', $fieldName)
        );

        $input = $validated[$command->fieldName()];
        $model = $command->modelOrFail();

        $result = $this->store
            ->modifyToMany($command->type(), $model, $fieldName)
            ->withRequest($command->request())
            ->detach($input);

        return Result::ok(new Payload($result, true));
    }
}
