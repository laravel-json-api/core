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
use LaravelJsonApi\Core\Extensions\Atomic\Values\OpCodeEnum;
use RuntimeException;

class OperationParser
{
    /**
     * @param ParsesOperationContainer $parsers
     */
    public function __construct(private readonly ParsesOperationContainer $parsers)
    {
    }

    /**
     * Parse the array to an operation.
     *
     * @param array $operation
     * @return Operation
     */
    public function parse(array $operation): Operation
    {
        $op = OpCodeEnum::tryFrom($operation['op'] ?? null);

        assert($op !== null, 'Operation array must have a valid op code.');

        foreach ($this->parsers->cursor($op) as $parser) {
            $parsed = $parser->parse($operation);

            if ($parsed !== null) {
                return $parsed;
            }
        }

        throw new RuntimeException('Unexpected operation array - could not parse to an atomic operation.');
    }
}
