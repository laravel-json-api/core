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

namespace LaravelJsonApi\Core\Tests\Unit\Extensions\Atomic\Operations;

use Illuminate\Contracts\Support\Arrayable;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\ListOfOperations;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Operation;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Create;
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
