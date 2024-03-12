<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Http\Actions\DetachRelationship;

use Closure;
use LaravelJsonApi\Core\Responses\NoContentResponse;
use LaravelJsonApi\Core\Responses\RelationshipResponse;

interface HandlesDetachRelationshipActions
{
    /**
     * Handle a detach relationship action.
     *
     * @param DetachRelationshipActionInput $action
     * @param Closure $next
     * @return RelationshipResponse|NoContentResponse
     */
    public function handle(
        DetachRelationshipActionInput $action,
        Closure $next,
    ): RelationshipResponse|NoContentResponse;
}
