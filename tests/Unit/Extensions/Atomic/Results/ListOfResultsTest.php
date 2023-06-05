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
