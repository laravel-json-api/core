<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Http\Actions\AttachRelationship;

use Closure;
use LaravelJsonApi\Core\Responses\NoContentResponse;
use LaravelJsonApi\Core\Responses\RelationshipResponse;

interface HandlesAttachRelationshipActions
{
    /**
     * Handle an attach relationship action.
     *
     * @param AttachRelationshipActionInput $action
     * @param Closure $next
     * @return RelationshipResponse|NoContentResponse
     */
    public function handle(
        AttachRelationshipActionInput $action,
        Closure $next,
    ): RelationshipResponse|NoContentResponse;
}
