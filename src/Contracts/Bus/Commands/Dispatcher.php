<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Contracts\Bus\Commands;

use LaravelJsonApi\Core\Bus\Commands\Command\Command;
use LaravelJsonApi\Core\Bus\Commands\Result;

interface Dispatcher
{
    /**
     * Dispatch a JSON:API command.
     *
     * @param Command $command
     * @return Result
     */
    public function dispatch(Command $command): Result;
}
