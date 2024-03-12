<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Extensions\Atomic\Values;

use Illuminate\Contracts\Support\Arrayable;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Ref;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\TestCase;

class RefTest extends TestCase
{
    /**
     * @return void
     */
    public function testItCanHaveIdWithoutRelationship(): void
    {
        $ref = new Ref(
            type: $type = new ResourceType('posts'),
            id: $id = new ResourceId('123'),
        );

        $expected = [
            'type' => $type->value,
            'id' => $id->value,
        ];

        $this->assertSame($type, $ref->type);
        $this->assertSame($id, $ref->id);
        $this->assertNull($ref->lid);
        $this->assertNull($ref->relationship);
        $this->assertInstanceOf(Arrayable::class, $ref);
        $this->assertSame($expected, $ref->toArray());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['ref' => $expected]),
            json_encode(['ref' => $ref]),
        );
    }

    /**
     * @return void
     */
    public function testItCanHaveIdWithRelationship(): void
    {
        $ref = new Ref(
            type: $type = new ResourceType('posts'),
            id: $id = new ResourceId('123'),
            relationship: 'comments',
        );

        $expected = [
            'type' => $type->value,
            'id' => $id->value,
            'relationship' => 'comments',
        ];

        $this->assertSame($type, $ref->type);
        $this->assertSame($id, $ref->id);
        $this->assertNull($ref->lid);
        $this->assertSame('comments', $ref->relationship);
        $this->assertSame($expected, $ref->toArray());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['ref' => $expected]),
            json_encode(['ref' => $ref]),
        );
    }

    /**
     * @return void
     */
    public function testItCanHaveLidWithoutRelationship(): void
    {
        $ref = new Ref(
            type: $type = new ResourceType('posts'),
            lid: $lid = new ResourceId('123'),
        );

        $expected = [
            'type' => $type->value,
            'lid' => $lid->value,
        ];

        $this->assertSame($type, $ref->type);
        $this->assertSame($lid, $ref->lid);
        $this->assertNull($ref->id);
        $this->assertNull($ref->relationship);
        $this->assertInstanceOf(Arrayable::class, $ref);
        $this->assertSame($expected, $ref->toArray());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['ref' => $expected]),
            json_encode(['ref' => $ref]),
        );
    }

    /**
     * @return void
     */
    public function testItCanHaveLidWithRelationship(): void
    {
        $ref = new Ref(
            type: $type = new ResourceType('posts'),
            lid: $lid = new ResourceId('123'),
            relationship: 'comments',
        );

        $expected = [
            'type' => $type->value,
            'lid' => $lid->value,
            'relationship' => 'comments',
        ];

        $this->assertSame($type, $ref->type);
        $this->assertSame($lid, $ref->lid);
        $this->assertNull($ref->id);
        $this->assertSame('comments', $ref->relationship);
        $this->assertSame($expected, $ref->toArray());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['ref' => $expected]),
            json_encode(['ref' => $ref]),
        );
    }

    /**
     * @return void
     */
    public function testItMustHaveIdOrLid(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Ref must have an id or lid.');

        new Ref(new ResourceType('posts'));
    }

    /**
     * @return void
     */
    public function testItCannotHaveBothIdAndLid(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Ref cannot have both an id and lid.');

        new Ref(
            type: new ResourceType('posts'),
            id: new ResourceId('123'),
            lid: new ResourceId('456'),
        );
    }

    /**
     * @return array<array<string>>
     */
    public static function invalidRelationshipProvider(): array
    {
        return [
            [''],
            ['  '],
        ];
    }

    /**
     * @param string $value
     * @return void
     * @dataProvider invalidRelationshipProvider
     */
    public function testItRejectsInvalidRelationship(string $value): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Relationship must be a non-empty string if provided.');

        new Ref(
            type: new ResourceType('posts'),
            id: new ResourceId('123'),
            relationship: $value,
        );
    }
}
