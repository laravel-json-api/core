<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Document;

use LaravelJsonApi\Core\Document\ResourceIdentifier;
use LaravelJsonApi\Core\Json\Hash;
use PHPUnit\Framework\TestCase;

class ResourceIdentifierTest extends TestCase
{
    public function test(): void
    {
        $identifier = new ResourceIdentifier('posts', '123');

        $this->assertSame('posts', $identifier->type());
        $this->assertSame('123', $identifier->id());
        $this->assertEquals($identifier, ResourceIdentifier::make('posts', '123'));
    }

    public function testZeroId(): void
    {
        $identifier = new ResourceIdentifier('posts', '0');

        $this->assertSame('0', $identifier->id());
    }

    /**
     * @return array
     */
    public static function emptyIdProvider(): array
    {
        return [
            [''],
            [' '],
            ['      '],
        ];
    }

    public function testIdIsEmpty(): void
    {
        $this->assertTrue(ResourceIdentifier::idIsEmpty(null));
        $this->assertFalse(ResourceIdentifier::idIsEmpty('0'));
        $this->assertFalse(ResourceIdentifier::idIsEmpty('1'));
    }

    /**
     * @param string $value
     * @return void
     * @dataProvider emptyIdProvider
     */
    public function testIdIsEmptyWithEmptyString(string $value): void
    {
        $this->assertTrue(ResourceIdentifier::idIsEmpty($value));
    }

    public function testItThrowsIfTypeIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('type');
        new ResourceIdentifier('', '1');
    }

    /**
     * @param string $value
     * @return void
     * @dataProvider emptyIdProvider
     */
    public function testItThrowsIfIdIsEmpty(string $value): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('id');
        new ResourceIdentifier('posts', $value);
    }

    public function testFromArray(): void
    {
        $expected = new ResourceIdentifier('posts', '123');

        $actual = ResourceIdentifier::fromArray([
            'type' => 'posts',
            'id' => '123',
        ]);

        $this->assertEquals($expected, $actual);
        $this->assertEquals(new Hash(), $actual->meta());
    }

    public function testFromArrayWithMeta(): void
    {
        $actual = ResourceIdentifier::fromArray([
            'type' => 'posts',
            'id' => '123',
            'meta' => [
                'foo' => 'bar',
            ],
        ]);

        $this->assertSame('posts', $actual->type());
        $this->assertSame('123', $actual->id());
        $this->assertEquals(new Hash(['foo' => 'bar']), $actual->meta());
    }

    public function testFromArrayWithoutType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('with a type and id');
        ResourceIdentifier::fromArray(['id' => '123']);
    }

    public function testFromArrayWithoutId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('with a type and id');
        ResourceIdentifier::fromArray(['type' => 'posts']);
    }
}