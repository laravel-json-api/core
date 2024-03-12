<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Bus\Commands\AttachRelationship;

use Closure;
use LaravelJsonApi\Core\Bus\Commands\Result;

interface HandlesAttachRelationshipCommands
{
    /**
     * @param AttachRelationshipCommand $command
     * @param Closure $next
     * @return Result
     */
    public function handle(AttachRelationshipCommand $command, Closure $next): Result;
}
