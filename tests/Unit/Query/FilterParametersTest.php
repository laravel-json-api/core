<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
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

    public function testValue(): void
    {
        $filters = FilterParameters::fromArray([
            'foo' => 'bar',
            'baz' => null,
        ]);

        $this->assertSame('bar', $filters->value('foo'));
        $this->assertNull($filters->value('baz', 'blah!'));
        $this->assertNull($filters->value('foobar'));
        $this->assertFalse($filters->value('foobar', false));
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
