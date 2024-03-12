<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Extensions\Atomic\Operations;

use Illuminate\Contracts\Support\Arrayable;
use LaravelJsonApi\Core\Document\Input\Values\ResourceIdentifier;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\UpdateToOne;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Href;
use LaravelJsonApi\Core\Extensions\Atomic\Values\OpCodeEnum;
use LaravelJsonApi\Core\Extensions\Atomic\Values\ParsedHref;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Ref;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\TestCase;

class UpdateToOneTest extends TestCase
{
    /**
     * @return UpdateToOne
     */
    public function testItHasHref(): UpdateToOne
    {
        $op = new UpdateToOne(
            $parsedHref = new ParsedHref(
                $href = new Href('/posts/123/relationships/author'),
                $type = new ResourceType('posts'),
                $id = new ResourceId('id'),
                $relationship = 'author',
            ),
            $identifier = new ResourceIdentifier(
                type: new ResourceType('users'),
                id: new ResourceId('456'),
            ),
        );

        $this->assertSame(OpCodeEnum::Update, $op->op);
        $this->assertSame($parsedHref, $op->target);
        $this->assertSame($href, $op->href());
        $this->assertEquals(new Ref(type: $type, id: $id, relationship: $relationship), $op->ref());
        $this->assertSame($identifier, $op->data);
        $this->assertEmpty($op->meta);
        $this->assertFalse($op->isCreating());
        $this->assertFalse($op->isUpdating());
        $this->assertFalse($op->isCreatingOrUpdating());
        $this->assertFalse($op->isDeleting());
        $this->assertSame('author', $op->getFieldName());
        $this->assertTrue($op->isUpdatingRelationship());
        $this->assertFalse($op->isAttachingRelationship());
        $this->assertFalse($op->isDetachingRelationship());
        $this->assertTrue($op->isModifyingRelationship());

        return $op;
    }

    /**
     * @return UpdateToOne
     */
    public function testItHasRef(): UpdateToOne
    {
        $op = new UpdateToOne(
            $ref = new Ref(
                type: new ResourceType('posts'),
                id: new ResourceId('123'),
                relationship: 'author',
            ),
            null,
            $meta = ['foo' => 'bar'],
        );

        $this->assertSame(OpCodeEnum::Update, $op->op);
        $this->assertSame($ref, $op->target);
        $this->assertNull($op->href());
        $this->assertSame($ref, $op->ref());
        $this->assertNull($op->data);
        $this->assertSame($meta, $op->meta);
        $this->assertFalse($op->isCreating());
        $this->assertFalse($op->isUpdating());
        $this->assertFalse($op->isCreatingOrUpdating());
        $this->assertFalse($op->isDeleting());
        $this->assertSame('author', $op->getFieldName());
        $this->assertTrue($op->isUpdatingRelationship());
        $this->assertFalse($op->isAttachingRelationship());
        $this->assertFalse($op->isDetachingRelationship());
        $this->assertTrue($op->isModifyingRelationship());

        return $op;
    }

    /**
     * @param UpdateToOne $op
     * @return void
     * @depends testItHasHref
     */
    public function testItIsArrayableWithHref(UpdateToOne $op): void
    {
        $expected = [
            'op' => $op->op->value,
            'href' => $op->href()->value,
            'data' => $op->data->toArray(),
        ];

        $this->assertInstanceOf(Arrayable::class, $op);
        $this->assertSame($expected, $op->toArray());
    }

    /**
     * @param UpdateToOne $op
     * @return void
     * @depends testItHasRef
     */
    public function testItIsArrayableWithRef(UpdateToOne $op): void
    {
        $expected = [
            'op' => $op->op->value,
            'ref' => $op->ref()->toArray(),
            'data' => null,
            'meta' => $op->meta,
        ];

        $this->assertInstanceOf(Arrayable::class, $op);
        $this->assertSame($expected, $op->toArray());
    }

    /**
     * @param UpdateToOne $op
     * @return void
     * @depends testItHasHref
     */
    public function testItIsJsonSerializableWithHref(UpdateToOne $op): void
    {
        $expected = [
            'op' => $op->op,
            'href' => $op->href(),
            'data' => $op->data,
        ];

        $this->assertJsonStringEqualsJsonString(
            json_encode(['atomic:operations' => [$expected]]),
            json_encode(['atomic:operations' => [$op]]),
        );
    }

    /**
     * @param UpdateToOne $op
     * @return void
     * @depends testItHasRef
     */
    public function testItIsJsonSerializableWithRef(UpdateToOne $op): void
    {
        $expected = [
            'op' => $op->op,
            'ref' => $op->ref(),
            'data' => $op->data,
            'meta' => $op->meta,
        ];

        $this->assertJsonStringEqualsJsonString(
            json_encode(['atomic:operations' => [$expected]]),
            json_encode(['atomic:operations' => [$op]]),
        );
    }
}
