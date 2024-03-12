<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Json;

use LaravelJsonApi\Core\Json\Hash;
use PHPUnit\Framework\TestCase;

class HashTest extends TestCase
{
    public function testCastHash(): void
    {
        $hash = new Hash(['foo' => 'bar']);

        $this->assertSame($hash, Hash::cast($hash));
    }

    public function testCastJsonSerializable(): void
    {
        $object = new class() implements \JsonSerializable {
            public function jsonSerialize(): array {
                return ['foo' => 'bar'];
            }
        };

        $expected = new Hash(['foo' => 'bar']);
        $actual = Hash::cast($object);

        $this->assertEquals($expected, $actual);
    }

    public function testCastArray(): void
    {
        $expected = new Hash(['foo' => 'bar']);
        $actual = Hash::cast(['foo' => 'bar']);

        $this->assertEquals($expected, $actual);
    }

    public function testCastStdClass(): void
    {
        $expected = new Hash(['foo' => 'bar']);
        $actual = Hash::cast((object) ['foo' => 'bar']);

        $this->assertEquals($expected, $actual);
    }

    public function testCastNull(): void
    {
        $actual = Hash::cast(null);

        $this->assertEquals(new Hash(), $actual);
    }

    public function testCastInvalidValue(): void
    {
        $this->expectException(\LogicException::class);
        Hash::cast(true);
    }

    public function testCamelize(): array
    {
        $actual = Hash::cast($value = [
            'foo_bar' => 'baz',
            'baz-bat' => 'bat',
            'foo' => [
                'foo_bar' => 'baz',
                'baz-bat' => 'bat',
            ],
        ])->camelize()->sortKeys()->jsonSerialize();

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
            ->sortKeys()
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
            ->sortKeys()
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
        ])->snake()->sortKeys()->all();

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
            ->sortKeys()
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
            ->sortKeys()
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
            ->sortKeys()
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
        ])->dasherize()->sortKeys()->all();

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
            ->sortKeys()
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
            ->sortKeys()
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
