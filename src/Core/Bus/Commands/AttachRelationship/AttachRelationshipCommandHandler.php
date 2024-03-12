<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Bus\Commands\AttachRelationship;

use LaravelJsonApi\Contracts\Store\Store;
use LaravelJsonApi\Core\Bus\Commands\AttachRelationship\Middleware\AuthorizeAttachRelationshipCommand;
use LaravelJsonApi\Core\Bus\Commands\AttachRelationship\Middleware\TriggerAttachRelationshipHooks;
use LaravelJsonApi\Core\Bus\Commands\Middleware\SetModelIfMissing;
use LaravelJsonApi\Core\Bus\Commands\Middleware\ValidateRelationshipCommand;
use LaravelJsonApi\Core\Bus\Commands\Result;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result as Payload;
use LaravelJsonApi\Core\Support\Contracts;
use LaravelJsonApi\Core\Support\PipelineFactory;
use UnexpectedValueException;

class AttachRelationshipCommandHandler
{
    /**
     * AttachRelationshipCommandHandler constructor
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
     * Execute an attach relationship command.
     *
     * @param AttachRelationshipCommand $command
     * @return Result
     */
    public function execute(AttachRelationshipCommand $command): Result
    {
        $pipes = [
            SetModelIfMissing::class,
            AuthorizeAttachRelationshipCommand::class,
            ValidateRelationshipCommand::class,
            TriggerAttachRelationshipHooks::class,
        ];

        $result = $this->pipelines
            ->pipe($command)
            ->through($pipes)
            ->via('handle')
            ->then(fn (AttachRelationshipCommand $cmd): Result => $this->handle($cmd));

        if ($result instanceof Result) {
            return $result;
        }

        throw new UnexpectedValueException('Expecting pipeline to return a command result.');
    }

    /**
     * Handle the command.
     *
     * @param AttachRelationshipCommand $command
     * @return Result
     */
    private function handle(AttachRelationshipCommand $command): Result
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
            ->attach($input);

        return Result::ok(new Payload($result, true));
    }
}
