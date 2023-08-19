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

namespace LaravelJsonApi\Core\Bus\Commands\UpdateRelationship;

use LaravelJsonApi\Contracts\Store\Store;
use LaravelJsonApi\Core\Bus\Commands\Middleware\SetModelIfMissing;
use LaravelJsonApi\Core\Bus\Commands\Middleware\ValidateRelationshipCommand;
use LaravelJsonApi\Core\Bus\Commands\Result;
use LaravelJsonApi\Core\Bus\Commands\UpdateRelationship\Middleware\AuthorizeUpdateRelationshipCommand;
use LaravelJsonApi\Core\Bus\Commands\UpdateRelationship\Middleware\TriggerUpdateRelationshipHooks;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result as Payload;
use LaravelJsonApi\Core\Support\Contracts;
use LaravelJsonApi\Core\Support\PipelineFactory;
use UnexpectedValueException;

class UpdateRelationshipCommandHandler
{
    /**
     * UpdateRelationshipCommandHandler constructor
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
     * Execute an update relationship command.
     *
     * @param UpdateRelationshipCommand $command
     * @return Result
     */
    public function execute(UpdateRelationshipCommand $command): Result
    {
        $pipes = [
            SetModelIfMissing::class,
            AuthorizeUpdateRelationshipCommand::class,
            ValidateRelationshipCommand::class,
            TriggerUpdateRelationshipHooks::class,
        ];

        $result = $this->pipelines
            ->pipe($command)
            ->through($pipes)
            ->via('handle')
            ->then(fn (UpdateRelationshipCommand $cmd): Result => $this->handle($cmd));

        if ($result instanceof Result) {
            return $result;
        }

        throw new UnexpectedValueException('Expecting pipeline to return a command result.');
    }

    /**
     * Handle the command.
     *
     * @param UpdateRelationshipCommand $command
     * @return Result
     */
    private function handle(UpdateRelationshipCommand $command): Result
    {
        $fieldName = $command->fieldName();
        $validated = $command->validated();

        Contracts::assert(
            array_key_exists($fieldName, $validated),
            sprintf('Relation %s must have a validation rule so that it is validated.', $fieldName)
        );

        $input = $validated[$command->fieldName()];
        $model = $command->modelOrFail();

        if ($command->toOne()) {
            $result = $this->store
                ->modifyToOne($command->type(), $model, $fieldName)
                ->withRequest($command->request())
                ->associate($input);
        } else {
            $result = $this->store
                ->modifyToMany($command->type(), $model, $fieldName)
                ->withRequest($command->request())
                ->sync($input);
        }

        return Result::ok(new Payload($result, true));
    }
}
