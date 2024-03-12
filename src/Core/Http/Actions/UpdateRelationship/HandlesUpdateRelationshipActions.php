<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Http\Actions\UpdateRelationship;

use Closure;
use LaravelJsonApi\Core\Responses\RelationshipResponse;

interface HandlesUpdateRelationshipActions
{
    /**
     * Handle an update relationship action.
     *
     * @param UpdateRelationshipActionInput $action
     * @param Closure $next
     * @return RelationshipResponse
     */
    public function handle(UpdateRelationshipActionInput $action, Closure $next): RelationshipResponse;
}
