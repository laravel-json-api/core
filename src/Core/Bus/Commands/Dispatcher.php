<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Bus\Commands;

use Illuminate\Contracts\Container\Container;
use LaravelJsonApi\Contracts\Bus\Commands\Dispatcher as DispatcherContract;
use LaravelJsonApi\Core\Bus\Commands\AttachRelationship\AttachRelationshipCommand;
use LaravelJsonApi\Core\Bus\Commands\AttachRelationship\AttachRelationshipCommandHandler;
use LaravelJsonApi\Core\Bus\Commands\Command\Command;
use LaravelJsonApi\Core\Bus\Commands\Destroy\DestroyCommand;
use LaravelJsonApi\Core\Bus\Commands\Destroy\DestroyCommandHandler;
use LaravelJsonApi\Core\Bus\Commands\DetachRelationship\DetachRelationshipCommand;
use LaravelJsonApi\Core\Bus\Commands\DetachRelationship\DetachRelationshipCommandHandler;
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
            AttachRelationshipCommand::class => AttachRelationshipCommandHandler::class,
            DetachRelationshipCommand::class => DetachRelationshipCommandHandler::class,
            default => throw new RuntimeException('Unexpected command class: ' . $commandClass),
        };
    }
}
