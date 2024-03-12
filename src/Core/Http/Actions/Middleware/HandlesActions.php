<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Http\Actions\Middleware;

use Closure;
use Illuminate\Contracts\Support\Responsable;
use LaravelJsonApi\Core\Http\Actions\Input\ActionInput;
use Symfony\Component\HttpFoundation\Response;

interface HandlesActions
{
    /**
     * Handle an action.
     *
     * @param ActionInput $action
     * @param Closure $next
     * @return Responsable|Response
     */
    public function handle(ActionInput $action, Closure $next): Responsable|Response;
}
