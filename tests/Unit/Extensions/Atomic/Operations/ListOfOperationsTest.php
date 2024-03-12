<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Extensions\Atomic\Operations;

use Illuminate\Contracts\Support\Arrayable;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Create;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\ListOfOperations;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Operation;
use PHPUnit\Framework\TestCase;

class ListOfOperationsTest extends TestCase
{
    /**
     * @return void
     */
    public function test(): void
    {
        $ops = new ListOfOperations(
            $a = $this->createMock(Operation::class),
            $b = $this->createMock(Create::class),
        );

        $a->method('toArray')->willReturn(['a' => 1]);
        $a->method('jsonSerialize')->willReturn(['a' => 2]);

        $b->method('toArray')->willReturn(['b' => 3]);
        $b->method('jsonSerialize')->willReturn(['b' => 4]);

        $arr = [
            ['a' => 1],
            ['b' => 3],
        ];

        $json = [
            ['a' => 2],
            ['b' => 4],
        ];

        $this->assertSame([$a, $b], iterator_to_array($ops));
        $this->assertSame([$a, $b], $ops->all());
        $this->assertCount(2, $ops);
        $this->assertInstanceOf(Arrayable::class, $ops);
        $this->assertSame($arr, $ops->toArray());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['ops' => $json]),
            json_encode(['ops' => $ops]),
        );
    }

    /**
     * @return void
     */
    public function testItCannotBeEmpty(): void
    {
        $this->expectException(\LogicException::class);
        new ListOfOperations();
    }
}
