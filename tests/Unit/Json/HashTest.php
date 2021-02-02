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

namespace LaravelJsonApi\Core\Tests\Unit\Json;

use LaravelJsonApi\Core\Json\Hash;
use PHPUnit\Framework\TestCase;

class HashTest extends TestCase
{

    public function testCamelize(): array
    {
        $actual = Hash::cast($value = [
            'foo_bar' => 'baz',
            'baz-bat' => 'bat',
            'foo' => [
                'foo_bar' => 'baz',
                'baz-bat' => 'bat',
            ],
        ])->camelize()->jsonSerialize();

        $this->assertSame($expected = [
            'bazBat' => 'bat',
            'foo' => [
                'fooBar' => 'baz',
                'bazBat' => 'bat',
            ],
            'fooBar' => 'baz',
        ], $actual);

        return [$value, $expected];
    }

    /**
     * @param array $args
     * @depends testCamelize
     */
    public function testUseCaseCamel(array $args): void
    {
        [$value, $expected] = $args;

        $actual = Hash::cast($value)
            ->useCase('camel')
            ->jsonSerialize();

        $this->assertSame($expected, $actual);
    }

    /**
     * @param array $args
     * @depends testCamelize
     */
    public function testUseCaseCamelize(array $args): void
    {
        [$value, $expected] = $args;

        $actual = Hash::cast($value)
            ->useCase('camelize')
            ->jsonSerialize();

        $this->assertSame($expected, $actual);
    }

    public function testSnake(): array
    {
        $actual = Hash::cast($value = [
            'fooBar' => 'baz',
            'baz-bat' => 'bat',
            'foo' => [
                'fooBar' => 'baz',
                'baz-bat' => 'bat',
            ],
        ])->snake()->sortedKeys()->all();

        $this->assertSame($expected = [
            'baz_bat' => 'bat',
            'foo' => [
                'foo_bar' => 'baz',
                'baz_bat' => 'bat',
            ],
            'foo_bar' => 'baz',
        ], $actual);

        return [$value, $expected];
    }

    /**
     * @param array $args
     * @depends testSnake
     */
    public function testUseCaseSnake(array $args): void
    {
        [$value, $expected] = $args;

        $actual = Hash::cast($value)
            ->useCase('snake')
            ->jsonSerialize();

        $this->assertSame($expected, $actual);
    }

    /**
     * @param array $args
     * @depends testSnake
     */
    public function testUnderscore(array $args): void
    {
        [$value, $expected] = $args;

        $actual = Hash::cast($value)
            ->underscore()
            ->jsonSerialize();

        $this->assertSame($expected, $actual);
    }

    /**
     * @param array $args
     * @depends testSnake
     */
    public function testUseCaseUnderscore(array $args): void
    {
        [$value, $expected] = $args;

        $actual = Hash::cast($value)
            ->useCase('underscore')
            ->jsonSerialize();

        $this->assertSame($expected, $actual);
    }

    public function testDasherize(): array
    {
        $actual = Hash::cast($value = [
            'fooBar' => 'baz',
            'baz_bat' => 'bat',
            'foo' => [
                'fooBar' => 'baz',
                'baz_bat' => 'bat',
            ],
        ])->dasherize()->sortedKeys()->all();

        $this->assertSame($expected = [
            'baz-bat' => 'bat',
            'foo' => [
                'foo-bar' => 'baz',
                'baz-bat' => 'bat',
            ],
            'foo-bar' => 'baz',
        ], $actual);

        return [$value, $expected];
    }

    /**
     * @param array $args
     * @depends testDasherize
     */
    public function testUseCaseDash(array $args): void
    {
        [$value, $expected] = $args;

        $actual = Hash::cast($value)
            ->useCase('dash')
            ->jsonSerialize();

        $this->assertSame($expected, $actual);
    }

    /**
     * @param array $args
     * @depends testDasherize
     */
    public function testUseCaseDasherize(array $args): void
    {
        [$value, $expected] = $args;

        $actual = Hash::cast($value)
            ->useCase('dasherize')
            ->jsonSerialize();

        $this->assertSame($expected, $actual);
    }

    public function testUseCaseNull(): void
    {
        $value = [
            'foo_bar' => 'baz',
            'baz-bat' => 'bat',
            'foo' => [
                'foo_bar' => 'baz',
                'baz-bat' => 'bat',
            ],
        ];

        $actual = Hash::cast($value)
            ->useCase('camelize')
            ->useCase(null)
            ->jsonSerialize();

        $this->assertEquals($value, $actual);
    }

    public function testItIsEmpty(): void
    {
        $hash = new Hash([]);

        $this->assertTrue($hash->isEmpty());
        $this->assertFalse($hash->isNotEmpty());
        $this->assertNull($hash->jsonSerialize());
    }

    public function testItIsNotEmpty(): void
    {
        $hash = new Hash(['foo' => 'bar']);

        $this->assertFalse($hash->isEmpty());
        $this->assertTrue($hash->isNotEmpty());
    }

    public function testItSortsValues(): void
    {
        $actual = Hash::cast(['foo' => 'b', 'bar' => 'a', 'baz' => 'c'])
            ->sorted()
            ->jsonSerialize();

        $this->assertSame(['bar' => 'a', 'foo' => 'b', 'baz' => 'c'], $actual);
    }
}
