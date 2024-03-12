<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Extensions\Atomic\Parsers;

use LaravelJsonApi\Core\Extensions\Atomic\Operations\Operation;

interface ParsesOperationFromArray
{
    /**
     * Parse an operation from an array.
     *
     * @param array $operation
     * @return Operation|null
     */
    public function parse(array $operation): ?Operation;
}
