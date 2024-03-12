<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Bus\Commands\UpdateRelationship;

use Closure;
use LaravelJsonApi\Core\Bus\Commands\Result;

interface HandlesUpdateRelationshipCommands
{
    /**
     * @param UpdateRelationshipCommand $command
     * @param Closure $next
     * @return Result
     */
    public function handle(UpdateRelationshipCommand $command, Closure $next): Result;
}
