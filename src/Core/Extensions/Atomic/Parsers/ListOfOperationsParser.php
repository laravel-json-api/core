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

use LaravelJsonApi\Core\Extensions\Atomic\Operations\ListOfOperations;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Operation;

class ListOfOperationsParser
{
    /**
     * ListOfOperationsParser constructor
     *
     * @param OperationParser $operationParser
     */
    public function __construct(private readonly OperationParser $operationParser)
    {
    }

    /**
     * Parse an array of operations to a list of operations object.
     *
     * @param array $operations
     * @return ListOfOperations
     */
    public function parse(array $operations): ListOfOperations
    {
        return new ListOfOperations(...array_map(
            fn(array $operation): Operation => $this->operationParser->parse($operation),
            $operations,
        ));
    }
}
