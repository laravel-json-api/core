<?php
/**
 * Copyright 2020 Cloud Creativity Limited
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

namespace LaravelJsonApi\Core\Tests\Unit\Support;

use LaravelJsonApi\Core\Support\Str;
use PHPUnit\Framework\TestCase;

class StrTest extends TestCase
{

    /**
     * @return array
     */
    public function dasherizeProvider(): array
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
    public function snakeProvider(): array
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
    public function underscoreProvider(): array
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
    public function camelizeProvider(): array
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
