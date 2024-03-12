<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Bus\Commands\Store\Middleware;

use Closure;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Schema\Container as SchemaContainer;
use LaravelJsonApi\Contracts\Validation\Container as ValidatorContainer;
use LaravelJsonApi\Contracts\Validation\ResourceErrorFactory;
use LaravelJsonApi\Contracts\Validation\CreationValidator;
use LaravelJsonApi\Core\Bus\Commands\Result;
use LaravelJsonApi\Core\Bus\Commands\Store\HandlesStoreCommands;
use LaravelJsonApi\Core\Bus\Commands\Store\StoreCommand;
use LaravelJsonApi\Core\Values\ResourceType;

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
        if ($command->mustValidate()) {
            $validator = $this
                ->validatorFor($command->type(), $command->request())
                ->make($command->operation());

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
                ->validatorFor($command->type(), $command->request())
                ->extract($command->operation());

            $command = $command->withValidated($data);
        }

        return $next($command);
    }

    /**
     * Make a store validator.
     *
     * @param ResourceType $type
     * @param Request|null $request
     * @return CreationValidator
     */
    private function validatorFor(ResourceType $type, ?Request $request): CreationValidator
    {
        return $this->validatorContainer
            ->validatorsFor($type)
            ->withRequest($request)
            ->store();
    }
}
