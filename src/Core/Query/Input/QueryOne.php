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

use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;

class QueryOne extends Query
{
    /**
     * QueryOne constructor
     *
     * @param ResourceType $type
     * @param ResourceId $id
     * @param array $parameters
     */
    public function __construct(
        ResourceType $type,
        public readonly ResourceId $id,
        array $parameters = []
    ) {
        parent::__construct(QueryCodeEnum::One, $type, $parameters);
    }
}
