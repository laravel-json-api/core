<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Extensions\Atomic\Parsers;

use LaravelJsonApi\Core\Extensions\Atomic\Operations\Create;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Operation;
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
            [$ops[1], $b = $this->createMock(Create::class)],
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
