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

namespace LaravelJsonApi\Core\Bus\Commands;

use Illuminate\Contracts\Container\Container;
use LaravelJsonApi\Contracts\Bus\Commands\Dispatcher as DispatcherContract;
use LaravelJsonApi\Core\Bus\Commands\Command\Command;
use LaravelJsonApi\Core\Bus\Commands\Destroy\DestroyCommand;
use LaravelJsonApi\Core\Bus\Commands\Destroy\DestroyCommandHandler;
use LaravelJsonApi\Core\Bus\Commands\Store\StoreCommand;
use LaravelJsonApi\Core\Bus\Commands\Store\StoreCommandHandler;
use LaravelJsonApi\Core\Bus\Commands\Update\UpdateCommand;
use LaravelJsonApi\Core\Bus\Commands\Update\UpdateCommandHandler;
use LaravelJsonApi\Core\Bus\Commands\UpdateRelationship\UpdateRelationshipCommand;
use LaravelJsonApi\Core\Bus\Commands\UpdateRelationship\UpdateRelationshipCommandHandler;
use RuntimeException;

class Dispatcher implements DispatcherContract
{
    /**
     * Dispatcher constructor
     *
     * @param Container $container
     */
    public function __construct(private readonly Container $container)
    {
    }

    /**
     * @inheritDoc
     */
    public function dispatch(Command $command): Result
    {
        $handler = $this->container->make(
            $binding = $this->handlerFor($command::class),
        );

        assert(
            is_object($handler) && method_exists($handler, 'execute'),
            'Unexpected value from container when resolving command - ' . $command::class,
        );

        $result = $handler->execute($command);

        assert($result instanceof Result, 'Unexpected value returned from command handler: ' . $binding);

        return $result;
    }

    /**
     * @param string $commandClass
     * @return string
     */
    private function handlerFor(string $commandClass): string
    {
        return match ($commandClass) {
            StoreCommand::class => StoreCommandHandler::class,
            UpdateCommand::class => UpdateCommandHandler::class,
            DestroyCommand::class => DestroyCommandHandler::class,
            UpdateRelationshipCommand::class => UpdateRelationshipCommandHandler::class,
            default => throw new RuntimeException('Unexpected command class: ' . $commandClass),
        };
    }
}
