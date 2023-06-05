<?php
/*
 * Copyright 2023 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Extensions\Atomic\Parsers;

use Illuminate\Contracts\Pipeline\Pipeline;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Operation;
use LaravelJsonApi\Core\Support\Contracts;
use UnexpectedValueException;

class OperationParser
{
    /**
     * OperationParser constructor
     *
     * @param Pipeline $pipeline
     */
    public function __construct(private readonly Pipeline $pipeline)
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
        Contracts::assert(
            !empty($operation['op'] ?? null),
            'Operation array must have an op code.',
        );

        $pipes = [
            StoreParser::class,
        ];

        $parsed = $this->pipeline
            ->send($operation)
            ->through($pipes)
            ->via('parse')
            ->then(static fn() => throw new \LogicException('Indeterminate operation.'));

        if ($parsed instanceof Operation) {
            return $parsed;
        }

        throw new UnexpectedValueException('Pipeline did not return an operation object.');
    }
}
