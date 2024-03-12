<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Http\Actions\Destroy;

use Closure;
use LaravelJsonApi\Core\Responses\MetaResponse;
use LaravelJsonApi\Core\Responses\NoContentResponse;

interface HandlesDestroyActions
{
    /**
     * Handle a destroy action.
     *
     * @param DestroyActionInput $action
     * @param Closure $next
     * @return MetaResponse|NoContentResponse
     */
    public function handle(DestroyActionInput $action, Closure $next): MetaResponse|NoContentResponse;
}
