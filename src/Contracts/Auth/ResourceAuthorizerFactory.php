<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

namespace LaravelJsonApi\Contracts\Auth;

use LaravelJsonApi\Core\Values\ResourceType;

interface ResourceAuthorizerFactory
{
    /**
     * Return a new resource authorizer instance.
     *
     * @param ResourceType|string $type
     * @return ResourceAuthorizer
     */
    public function make(ResourceType|string $type): ResourceAuthorizer;
}