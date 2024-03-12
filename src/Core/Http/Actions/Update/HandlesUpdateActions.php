<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Http\Actions\Update;

use Closure;
use LaravelJsonApi\Core\Responses\DataResponse;

interface HandlesUpdateActions
{
    /**
     * Handle an update action.
     *
     * @param UpdateActionInput $action
     * @param Closure $next
     * @return DataResponse
     */
    public function handle(UpdateActionInput $action, Closure $next): DataResponse;
}
