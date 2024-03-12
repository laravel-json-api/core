<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
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
