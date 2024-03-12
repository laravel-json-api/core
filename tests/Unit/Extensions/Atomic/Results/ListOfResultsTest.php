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

use LaravelJsonApi\Core\Extensions\Atomic\Results\ListOfResults;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result;
use PHPUnit\Framework\TestCase;

class ListOfResultsTest extends TestCase
{
    /**
     * @return void
     */
    public function testItIsNotEmpty(): void
    {
        $results = new ListOfResults(
            $a = new Result(null, false),
            $b = new Result(null, false, ['foo' => 'bar']),
            $c = new Result(new \stdClass(), true),
        );

        $this->assertSame([$a, $b, $c], iterator_to_array($results));
        $this->assertSame([$a, $b, $c], $results->all());
        $this->assertCount(3, $results);
        $this->assertFalse($results->isEmpty());
        $this->assertTrue($results->isNotEmpty());
    }

    /**
     * @return void
     */
    public function testItIsEmpty(): void
    {
        $results = new ListOfResults(
            $a = new Result(null, false),
            $b = new Result(null, false),
            $c = new Result(null, false),
        );

        $this->assertSame([$a, $b, $c], iterator_to_array($results));
        $this->assertSame([$a, $b, $c], $results->all());
        $this->assertCount(3, $results);
        $this->assertTrue($results->isEmpty());
        $this->assertFalse($results->isNotEmpty());
    }

    /**
     * @return void
     */
    public function testItMustHaveAtLeastOneResult(): void
    {
        $this->expectException(\LogicException::class);
        new ListOfResults();
    }
}
