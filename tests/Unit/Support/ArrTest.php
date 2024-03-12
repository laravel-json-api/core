<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

namespace LaravelJsonApi\Core\Tests\Unit\Support;

use LaravelJsonApi\Core\Support\Arr;
use PHPUnit\Framework\TestCase;

class ArrTest extends TestCase
{

    public function testCamelize(): void
    {
        $actual = Arr::camelize([
            'numeric',
            'foo' => 'bar',
            'foo-bar' => 'foobar',
            'baz_bat' => 'bazbat',
            'extra-values' => [
                'fooBar' => 'foobar',
                'BazBat' => 'bazbat',
            ],
        ]);

        $this->assertEquals([
            'numeric',
            'foo' => 'bar',
            'fooBar' => 'foobar',
            'bazBat' => 'bazbat',
            'extraValues' => [
                'fooBar' => 'foobar',
                'bazBat' => 'bazbat',
            ],
        ], $actual);
    }

    public function testDasherize(): void
    {
        $actual = Arr::dasherize([
            'numeric',
            'foo' => 'bar',
            'fooBar' => 'foobar',
            'baz_bat' => 'bazbat',
            'extraValues' => [
                'fooBar' => 'foobar',
                'BazBat' => 'bazbat',
                'foo-baz' => 'foobaz',
            ],
        ]);

        $this->assertEquals([
            'numeric',
            'foo' => 'bar',
            'foo-bar' => 'foobar',
            'baz-bat' => 'bazbat',
            'extra-values' => [
                'foo-bar' => 'foobar',
                'baz-bat' => 'bazbat',
                'foo-baz' => 'foobaz',
            ],
        ], $actual);
    }

    public function testUnderscore(): void
    {
        $actual = Arr::underscore([
            'numeric',
            'foo' => 'bar',
            'fooBar' => 'foobar',
            'BazBat' => 'bazbat',
            'extraValues' => [
                'foo_bar' => 'foobar',
                'BazBat' => 'bazbat',
                'foo-baz' => 'foobaz',
            ],
        ]);

        $this->assertEquals([
            'numeric',
            'foo' => 'bar',
            'foo_bar' => 'foobar',
            'baz_bat' => 'bazbat',
            'extra_values' => [
                'foo_bar' => 'foobar',
                'baz_bat' => 'bazbat',
                'foo_baz' => 'foobaz',
            ],
        ], $actual);
    }

    /**
     * @return array
     */
    public static function methodsProvider(): array
    {
        return [
            ['camelize'],
            ['decamelize'],
            ['dasherize'],
            ['underscore'],
        ];
    }

    /**
     * Test that the conversion methods accept null as a value.
     *
     * @param string $method
     * @dataProvider methodsProvider
     */
    public function testNull(string $method): void
    {
        $actual = call_user_func(Arr::class . "::{$method}", null);

        $this->assertSame([], $actual);
    }
}
