<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Bus\Commands\Update\Middleware;

use Closure;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Schema\Container as SchemaContainer;
use LaravelJsonApi\Contracts\Validation\Container as ValidatorContainer;
use LaravelJsonApi\Contracts\Validation\ResourceErrorFactory;
use LaravelJsonApi\Contracts\Validation\UpdateValidator;
use LaravelJsonApi\Core\Bus\Commands\Result;
use LaravelJsonApi\Core\Bus\Commands\Update\HandlesUpdateCommands;
use LaravelJsonApi\Core\Bus\Commands\Update\UpdateCommand;
use LaravelJsonApi\Core\Values\ResourceType;

class ValidateUpdateCommand implements HandlesUpdateCommands
{
    /**
     * ValidateUpdateCommand constructor
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
    public function handle(UpdateCommand $command, Closure $next): Result
    {
        if ($command->mustValidate()) {
            $validator = $this
                ->validatorFor($command->type(), $command->request())
                ->make($command->operation(), $command->modelOrFail());

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
                ->extract($command->operation(), $command->modelOrFail());

            $command = $command->withValidated($data);
        }

        return $next($command);
    }

    /**
     * Make an update validator.
     *
     * @param ResourceType $type
     * @param Request|null $request
     * @return UpdateValidator
     */
    private function validatorFor(ResourceType $type, ?Request $request): UpdateValidator
    {
        return $this->validatorContainer
            ->validatorsFor($type)
            ->withRequest($request)
            ->update();
    }
}
