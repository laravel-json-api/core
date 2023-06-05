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

namespace LaravelJsonApi\Core\Tests\Unit\Extensions\Atomic\Results;

use LaravelJsonApi\Core\Extensions\Atomic\Results\Result;
use PHPUnit\Framework\TestCase;

class ResultTest extends TestCase
{
    /**
     * @return void
     */
    public function testItIsEmpty(): void
    {
        $result = new Result(null, false);

        $this->assertNull($result->data);
        $this->assertFalse($result->hasData);
        $this->assertEmpty($result->meta);
        $this->assertTrue($result->isEmpty());
        $this->assertFalse($result->isNotEmpty());
    }

    /**
     * @return void
     */
    public function testItIsMetaOnly(): void
    {
        $result = new Result(null, false, $meta = ['foo' => 'bar']);

        $this->assertNull($result->data);
        $this->assertFalse($result->hasData);
        $this->assertSame($meta, $result->meta);
        $this->assertFalse($result->isEmpty());
        $this->assertTrue($result->isNotEmpty());
    }

    /**
     * @return void
     */
    public function testItHasNullData(): void
    {
        $result = new Result(null, true);

        $this->assertNull($result->data);
        $this->assertTrue($result->hasData);
        $this->assertEmpty($result->meta);
        $this->assertFalse($result->isEmpty());
        $this->assertTrue($result->isNotEmpty());
    }

    /**
     * @return void
     */
    public function testItHasData(): void
    {
        $result = new Result($expected = new \stdClass(), true);

        $this->assertSame($expected, $result->data);
        $this->assertTrue($result->hasData);
        $this->assertEmpty($result->meta);
        $this->assertFalse($result->isEmpty());
        $this->assertTrue($result->isNotEmpty());
    }

    /**
     * @return void
     */
    public function testItHasDataAndMeta(): void
    {
        $result = new Result(
            $expected = new \stdClass(),
            true,
            $meta = ['foo' => 'bar'],
        );

        $this->assertSame($expected, $result->data);
        $this->assertTrue($result->hasData);
        $this->assertSame($meta, $result->meta);
        $this->assertFalse($result->isEmpty());
        $this->assertTrue($result->isNotEmpty());
    }

    /**
     * @return void
     */
    public function testItHasIncorrectHasDataValue(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Result data must be null when result has no data.');

        new Result(new \stdClass(), false);
    }
}
