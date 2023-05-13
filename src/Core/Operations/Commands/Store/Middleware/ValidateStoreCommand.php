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

namespace LaravelJsonApi\Core\Operations\Commands\Store\Middleware;

use LaravelJsonApi\Contracts\Operations\Commands\Store\StoreCommandMiddleware;
use LaravelJsonApi\Contracts\Operations\Result\Result as ResultContract;
use LaravelJsonApi\Contracts\Schema\Container as SchemaContainer;
use LaravelJsonApi\Contracts\Validation\Container as ValidatorContainer;
use LaravelJsonApi\Contracts\Validation\ResourceErrorFactory;
use LaravelJsonApi\Core\Document\ResourceObject;
use LaravelJsonApi\Core\Operations\Commands\Store\StoreCommand;
use LaravelJsonApi\Core\Operations\Result\Result;

class ValidateStoreCommand implements StoreCommandMiddleware
{
    /**
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
    public function __invoke(StoreCommand $command, \Closure $next): ResultContract
    {
        if ($command->mustValidate()) {
            $validator = $this->validatorContainer
                ->validatorsFor($command->type())
                ->store($this->validationData($command));

            if ($validator->fails()) {
                return Result::failed(
                    $this->errorFactory->make(
                        $this->schemaContainer->schemaFor($command->type()),
                        $validator,
                    ),
                );
            }

            $command = $command->withValidated($validator->validated());
        }

        if (!$command->isValidated()) {
            $command = $command->withValidated(
                $this->validationData($command),
            );
        }

        return $next($command);
    }

    /**
     * @param StoreCommand $command
     * @return array
     */
    private function validationData(StoreCommand $command): array
    {
        return ResourceObject::fromArray($command->data())->all();
    }
}
