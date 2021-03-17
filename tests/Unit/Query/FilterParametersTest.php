<?php
/*
 * Copyright 2021 Cloud Creativity Limited
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

namespace LaravelJsonApi\Core\Tests\Unit\Query;

use LaravelJsonApi\Core\Query\FilterParameter;
use LaravelJsonApi\Core\Query\FilterParameters;
use PHPUnit\Framework\TestCase;

class FilterParametersTest extends TestCase
{

    public function testCastWithArray(): FilterParameters
    {
        $filters = FilterParameters::cast($value = [
            'foo' => 'bar',
            'baz' => 'bat',
        ]);

        $this->assertSame('bar', $filters->get('foo')->value());
        $this->assertSame('bat', $filters->get('baz')->value());
        $this->assertNull($filters->get('foobar'));

        $this->assertEquals($value, $filters->toArray());
        $this->assertCount(2, $filters);
        $this->assertFalse($filters->isEmpty());
        $this->assertTrue($filters->isNotEmpty());

        return $filters;
    }

    /**
     * @param FilterParameters $expected
     * @depends testCastWithArray
     */
    public function testCastWithEnumerable(FilterParameters $expected): void
    {
        $actual = FilterParameters::cast(collect($expected->toArray()));

        $this->assertSame($expected->toArray(), $actual->toArray());
    }

    /**
     * @param FilterParameters $expected
     * @depends testCastWithArray
     */
    public function testNullable(FilterParameters $expected): void
    {
        $this->assertNull(FilterParameters::nullable(null));
        $this->assertEquals($expected, FilterParameters::nullable($expected->toArray()));
    }

    public function testEmpty(): void
    {
        $filters = new FilterParameters();

        $this->assertTrue($filters->isEmpty());
        $this->assertFalse($filters->isNotEmpty());
        $this->assertEquals($filters, FilterParameters::cast(null));
    }

    public function testExists(): void
    {
        $filters = FilterParameters::fromArray(['foo' => 'bar']);

        $this->assertTrue($filters->exists('foo'));
        $this->assertFalse($filters->exists('bazbat'));
    }

    public function testFilter(): void
    {
        $filters = FilterParameters::fromArray([
            'foo' => 'bar',
            'baz' => 'bat',
        ]);

        $actual = $filters->filter(fn(FilterParameter $param) => 'baz' === $param->key());

        $this->assertNotSame($filters, $actual);
        $this->assertEquals(['foo' => 'bar', 'baz' => 'bat'], $filters->toArray());
        $this->assertEquals(['baz' => 'bat'], $actual->toArray());
    }

    public function testReject(): void
    {
        $filters = FilterParameters::fromArray([
            'foo' => 'bar',
            'baz' => 'bat',
        ]);

        $actual = $filters->reject(fn(FilterParameter $param) => 'baz' === $param->key());

        $this->assertNotSame($filters, $actual);
        $this->assertEquals(['foo' => 'bar', 'baz' => 'bat'], $filters->toArray());
        $this->assertEquals(['foo' => 'bar'], $actual->toArray());
    }
}
