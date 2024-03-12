<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Bus\Commands\Middleware;

use Closure;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Schema\Container as SchemaContainer;
use LaravelJsonApi\Contracts\Validation\Container as ValidatorContainer;
use LaravelJsonApi\Contracts\Validation\RelationshipValidator;
use LaravelJsonApi\Contracts\Validation\ResourceErrorFactory;
use LaravelJsonApi\Core\Bus\Commands\Command\Command;
use LaravelJsonApi\Core\Bus\Commands\Command\IsRelatable;
use LaravelJsonApi\Core\Bus\Commands\Result;
use LaravelJsonApi\Core\Bus\Commands\UpdateRelationship\HandlesUpdateRelationshipCommands;
use LaravelJsonApi\Core\Values\ResourceType;

class ValidateRelationshipCommand implements HandlesUpdateRelationshipCommands
{
    /**
     * ValidateRelationshipCommand constructor
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
    public function handle(Command&IsRelatable $command, Closure $next): Result
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
     * Make a relationship validator.
     *
     * @param ResourceType $type
     * @param Request|null $request
     * @return RelationshipValidator
     */
    private function validatorFor(ResourceType $type, ?Request $request): RelationshipValidator
    {
        return $this->validatorContainer
            ->validatorsFor($type)
            ->withRequest($request)
            ->relation();
    }
}
