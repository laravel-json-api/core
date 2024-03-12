<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Http\Actions\Store;

use Closure;
use LaravelJsonApi\Core\Responses\DataResponse;

interface HandlesStoreActions
{
    /**
     * Handle a store action.
     *
     * @param StoreActionInput $action
     * @param Closure $next
     * @return DataResponse
     */
    public function handle(StoreActionInput $action, Closure $next): DataResponse;
}
