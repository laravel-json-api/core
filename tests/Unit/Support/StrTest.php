<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

namespace LaravelJsonApi\Core\Tests\Unit\Support;

use LaravelJsonApi\Core\Support\Str;
use PHPUnit\Framework\TestCase;

class StrTest extends TestCase
{

    /**
     * @return array
     */
    public static function dasherizeProvider(): array
    {
        return [
            'simple' => ['foo', 'foo'],
            'underscored' => ['foo_bar', 'foo-bar'],
            'camel' => ['fooBar', 'foo-bar'],
            'dash' => ['foo-bar', 'foo-bar'],
        ];
    }

    /**
     * @param string $value
     * @param string $expected
     * @dataProvider dasherizeProvider
     */
    public function testDasherize(string $value, string $expected): void
    {
        $this->assertSame($expected, Str::dasherize($value));
    }

    /**
     * @return array
     */
    public static function snakeProvider(): array
    {
        return [
            'simple' => ['foo', 'foo'],
            'camel' => ['fooBar', 'foo_bar'],
            'multi-camel' => ['fooBarBazBat', 'foo_bar_baz_bat'],
            'underscore' => ['foo_bar', 'foo_bar'],
        ];
    }

    /**
     * @param string $value
     * @param string $expected
     * @dataProvider snakeProvider
     */
    public function testSnake(string $value, string $expected): void
    {
        $this->assertSame($expected, Str::snake($value));
    }

    /**
     * @return array
     */
    public static function underscoreProvider(): array
    {
        return [
            ['foo', 'foo'],
            ['fooBar', 'foo_bar'],
            ['fooBarBazBat', 'foo_bar_baz_bat'],
            ['foo_bar', 'foo_bar'],
            ['foo-bar', 'foo_bar'],
            ['foo-bar-baz-bat', 'foo_bar_baz_bat'],
        ];
    }

    /**
     * @param string $value
     * @param string $expected
     * @dataProvider underscoreProvider
     */
    public function testUnderscore(string $value, string $expected): void
    {
        $this->assertSame($expected, Str::underscore($value));
    }

    /**
     * @return array
     */
    public static function camelizeProvider(): array
    {
        return [
            ['foo', 'foo'],
            ['foo-bar', 'fooBar'],
            ['foo_bar', 'fooBar'],
            ['foo_bar_baz_bat', 'fooBarBazBat'],
            ['fooBar', 'fooBar'],
        ];
    }

    /**
     * @param string $value
     * @param string $expected
     * @dataProvider camelizeProvider
     */
    public function testCamelizeAndClassify(string $value, string $expected)
    {
        $this->assertSame($expected, Str::camelize($value), 'camelize');
        $this->assertSame(ucfirst($expected), Str::classify($value), 'classify');
    }
}
