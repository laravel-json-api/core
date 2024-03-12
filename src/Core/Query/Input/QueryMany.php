<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Query\Input;

use LaravelJsonApi\Core\Values\ResourceType;

class QueryMany extends Query
{
    /**
     * QueryMany constructor
     *
     * @param ResourceType $type
     * @param array $parameters
     */
    public function __construct(ResourceType $type, array $parameters = [])
    {
        parent::__construct(QueryCodeEnum::Many, $type, $parameters);
    }
}
