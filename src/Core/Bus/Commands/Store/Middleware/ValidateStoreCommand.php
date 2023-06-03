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

namespace LaravelJsonApi\Core\Bus\Commands\Store\Middleware;

use Closure;
use LaravelJsonApi\Contracts\Schema\Container as SchemaContainer;
use LaravelJsonApi\Contracts\Validation\Container as ValidatorContainer;
use LaravelJsonApi\Contracts\Validation\ResourceErrorFactory;
use LaravelJsonApi\Contracts\Validation\StoreValidator;
use LaravelJsonApi\Core\Bus\Commands\Result;
use LaravelJsonApi\Core\Bus\Commands\Store\HandlesStoreCommands;
use LaravelJsonApi\Core\Bus\Commands\Store\StoreCommand;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;

class ValidateStoreCommand implements HandlesStoreCommands
{
    /**
     * ValidateStoreCommand constructor
     *
     * @param ValidatorContainer $validatorContainer
     * @param SchemaContainer $schemaContainer
     * @param ResourceErrorFactory $errorFactory
     */
    public function __construct(
        private readonly ValidatorContainer $validatorContainer,
        private readonly SchemaContainer $schemaContainer,
        private readonly ResourceErrorFactory $errorFactory,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function handle(StoreCommand $command, Closure $next): Result
    {
        $operation = $command->operation();

        if ($command->mustValidate()) {
            $validator = $this
                ->validatorFor($command->type())
                ->make($command->request(), $operation);

            if ($validator->fails()) {
                return Result::failed(
                    $this->errorFactory->make(
                        $this->schemaContainer->schemaFor($command->type()),
                        $validator,
                    ),
                );
            }

            $command = $command->withValidated(
                $validator->validated(),
            );
        }

        if ($command->isNotValidated()) {
            $data = $this
                ->validatorFor($command->type())
                ->extract($operation);

            $command = $command->withValidated($data);
        }

        return $next($command);
    }

    /**
     * Make a store validator.
     *
     * @param ResourceType $type
     * @return StoreValidator
     */
    private function validatorFor(ResourceType $type): StoreValidator
    {
        return $this->validatorContainer
            ->validatorsFor($type)
            ->store();
    }
}
