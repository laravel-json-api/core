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
use LaravelJsonApi\Contracts\Store\Store;
use LaravelJsonApi\Core\Bus\Commands\Command\Command;
use LaravelJsonApi\Core\Bus\Commands\Command\IsIdentifiable;
use LaravelJsonApi\Core\Bus\Commands\Result;
use LaravelJsonApi\Core\Store\LazyModel;

class SetModelIfMissing
{
    /**
     * SetModelIfMissing constructor
     *
     * @param Store $store
     */
    public function __construct(private readonly Store $store)
    {
    }

    /**
     * Handle an identifiable command.
     *
     * @param IsIdentifiable&Command $command
     * @param Closure $next
     * @return Result
     */
    public function handle(Command&IsIdentifiable $command, Closure $next): Result
    {
        if ($command->model() === null) {
            $command = $command->withModel(new LazyModel(
                $this->store,
                $command->type(),
                $command->id(),
            ));
        }

        return $next($command);
    }
}
