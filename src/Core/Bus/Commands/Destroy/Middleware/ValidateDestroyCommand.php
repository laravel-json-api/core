<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Bus\Commands\Destroy\Middleware;

use Closure;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Validation\Container as ValidatorContainer;
use LaravelJsonApi\Contracts\Validation\DeletionErrorFactory;
use LaravelJsonApi\Contracts\Validation\DeletionValidator;
use LaravelJsonApi\Core\Bus\Commands\Destroy\DestroyCommand;
use LaravelJsonApi\Core\Bus\Commands\Destroy\HandlesDestroyCommands;
use LaravelJsonApi\Core\Bus\Commands\Result;
use LaravelJsonApi\Core\Values\ResourceType;

class ValidateDestroyCommand implements HandlesDestroyCommands
{
    /**
     * ValidateDestroyCommand constructor
     *
     * @param ValidatorContainer $validatorContainer
     * @param DeletionErrorFactory $errorFactory
     */
    public function __construct(
        private readonly ValidatorContainer $validatorContainer,
        private readonly DeletionErrorFactory $errorFactory,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function handle(DestroyCommand $command, Closure $next): Result
    {
        if ($command->mustValidate()) {
            $validator = $this
                ->validatorFor($command->type(), $command->request())
                ?->make($command->operation(), $command->modelOrFail());

            if ($validator?->fails()) {
                return Result::failed(
                    $this->errorFactory->make($validator),
                );
            }

            $command = $command->withValidated(
                $validator?->validated() ?? [],
            );
        }

        if ($command->isNotValidated()) {
            $data = $this
                ->validatorFor($command->type(), $command->request())
                ?->extract($command->operation(), $command->modelOrFail());

            $command = $command->withValidated($data ?? []);
        }

        return $next($command);
    }

    /**
     * Make a destroy validator.
     *
     * @param ResourceType $type
     * @param Request|null $request
     * @return DeletionValidator|null
     */
    private function validatorFor(ResourceType $type, ?Request $request): ?DeletionValidator
    {
        return $this->validatorContainer
            ->validatorsFor($type)
            ->withRequest($request)
            ->destroy();
    }
}
