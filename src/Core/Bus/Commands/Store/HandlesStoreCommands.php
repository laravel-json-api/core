<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Bus\Commands\Store;

use Closure;
use LaravelJsonApi\Core\Bus\Commands\Result;

interface HandlesStoreCommands
{
    /**
     * @param StoreCommand $command
     * @param Closure $next
     * @return Result
     */
    public function handle(StoreCommand $command, Closure $next): Result;
}
