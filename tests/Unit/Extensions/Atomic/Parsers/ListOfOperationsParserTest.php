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

namespace LaravelJsonApi\Core\Tests\Unit\Extensions\Atomic\Parsers;

use LaravelJsonApi\Core\Extensions\Atomic\Operations\Operation;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Store;
use LaravelJsonApi\Core\Extensions\Atomic\Parsers\ListOfOperationsParser;
use LaravelJsonApi\Core\Extensions\Atomic\Parsers\OperationParser;
use PHPUnit\Framework\TestCase;

class ListOfOperationsParserTest extends TestCase
{
    /**
     * @return void
     */
    public function test(): void
    {
        $ops = [
            ['op' => 'add'],
            ['op' => 'remove'],
        ];

        $sequence = [
            [$ops[0], $a = $this->createMock(Operation::class)],
            [$ops[1], $b = $this->createMock(Store::class)],
        ];

        $operationParser = $this->createMock(OperationParser::class);
        $operationParser
            ->expects($this->exactly(2))
            ->method('parse')
            ->willReturnCallback(function (array $op) use (&$sequence): Operation {
                [$expected, $result] = array_shift($sequence);
                $this->assertSame($expected, $op);
                return $result;
            });

        $parser = new ListOfOperationsParser($operationParser);
        $actual = $parser->parse($ops);

        $this->assertSame([$a, $b], $actual->all());
    }
}
