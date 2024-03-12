<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Contracts\Auth;

use LaravelJsonApi\Core\Values\ResourceType;

interface Container
{
    /**
     * Resolve the authorizer for the supplied resource type from the container.
     *
     * @param ResourceType|string $type
     * @return Authorizer
     */
    public function authorizerFor(ResourceType|string $type): Authorizer;
}
