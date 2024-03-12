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
use LaravelJsonApi\Core\Document\Input\Values\ResourceObject;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Update;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Href;
use LaravelJsonApi\Core\Extensions\Atomic\Values\OpCodeEnum;
use LaravelJsonApi\Core\Extensions\Atomic\Values\ParsedHref;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Ref;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\TestCase;

class UpdateTest extends TestCase
{
    /**
     * @return Update
     */
    public function testItHasHref(): Update
    {
        $op = new Update(
            $parsedHref = new ParsedHref(
                $href = new Href('/posts/123'),
                new ResourceType('posts'),
                new ResourceId('123'),
            ),
            $resource = new ResourceObject(
                type: $type = new ResourceType('posts'),
                id: $id = new ResourceId('123'),
                attributes: ['title' => 'Hello World!']
            ),
        );

        $this->assertSame(OpCodeEnum::Update, $op->op);
        $this->assertSame($parsedHref, $op->target);
        $this->assertSame($href, $op->href());
        $this->assertEquals(new Ref(type: $type, id: $id), $op->ref());
        $this->assertSame($resource, $op->data);
        $this->assertEmpty($op->meta);
        $this->assertFalse($op->isCreating());
        $this->assertTrue($op->isUpdating());
        $this->assertTrue($op->isCreatingOrUpdating());
        $this->assertFalse($op->isDeleting());
        $this->assertNull($op->getFieldName());
        $this->assertFalse($op->isUpdatingRelationship());
        $this->assertFalse($op->isAttachingRelationship());
        $this->assertFalse($op->isDetachingRelationship());
        $this->assertFalse($op->isModifyingRelationship());

        return $op;
    }

    /**
     * @return Update
     */
    public function testItHasRef(): Update
    {
        $op = new Update(
            $ref = new Ref(new ResourceType('posts'), new ResourceId('123')),
            $resource = new ResourceObject(
                type: new ResourceType('posts'),
                id: new ResourceId('123'),
                attributes: ['title' => 'Hello World!']
            ),
        );

        $this->assertSame(OpCodeEnum::Update, $op->op);
        $this->assertSame($ref, $op->target);
        $this->assertNull($op->href());
        $this->assertSame($ref, $op->ref());
        $this->assertSame($resource, $op->data);
        $this->assertEmpty($op->meta);

        return $op;
    }

    /**
     * @return Update
     */
    public function testItIsMissingTargetWithMeta(): Update
    {
        $op = new Update(
            null,
            $resource = new ResourceObject(
                type: $type = new ResourceType('posts'),
                id: $id = new ResourceId('123'),
                attributes: ['title' => 'Hello World!']
            ),
            $meta = ['foo' => 'bar'],
        );

        $ref = new Ref(type: $type, id: $id);

        $this->assertSame(OpCodeEnum::Update, $op->op);
        $this->assertNull($op->target);
        $this->assertNull($op->href());
        $this->assertEquals($ref, $op->ref());
        $this->assertSame($resource, $op->data);
        $this->assertSame($meta, $op->meta);

        return $op;
    }

    /**
     * @param Update $op
     * @return void
     * @depends testItHasHref
     */
    public function testItIsArrayableWithHref(Update $op): void
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
     * @param Update $op
     * @return void
     * @depends testItHasRef
     */
    public function testItIsArrayableWithRef(Update $op): void
    {
        $expected = [
            'op' => $op->op->value,
            'ref' => $op->ref()->toArray(),
            'data' => $op->data->toArray(),
        ];

        $this->assertInstanceOf(Arrayable::class, $op);
        $this->assertSame($expected, $op->toArray());
    }

    /**
     * @param Update $op
     * @return void
     * @depends testItIsMissingTargetWithMeta
     */
    public function testItIsArrayableWithoutHrefAndWithMeta(Update $op): void
    {
        $expected = [
            'op' => $op->op->value,
            'data' => $op->data->toArray(),
            'meta' => $op->meta,
        ];

        $this->assertInstanceOf(Arrayable::class, $op);
        $this->assertSame($expected, $op->toArray());
    }

    /**
     * @param Update $op
     * @return void
     * @depends testItHasHref
     */
    public function testItIsJsonSerializableWithHref(Update $op): void
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
     * @param Update $op
     * @return void
     * @depends testItHasRef
     */
    public function testItIsJsonSerializableWithRef(Update $op): void
    {
        $expected = [
            'op' => $op->op,
            'ref' => $op->ref(),
            'data' => $op->data,
        ];

        $this->assertJsonStringEqualsJsonString(
            json_encode(['atomic:operations' => [$expected]]),
            json_encode(['atomic:operations' => [$op]]),
        );
    }

    /**
     * @param Update $op
     * @return void
     * @depends testItIsMissingTargetWithMeta
     */
    public function testItIsJsonSerializableWithoutHrefAndWithMeta(Update $op): void
    {
        $expected = [
            'op' => $op->op,
            'data' => $op->data,
            'meta' => $op->meta,
        ];

        $this->assertJsonStringEqualsJsonString(
            json_encode(['atomic:operations' => [$expected]]),
            json_encode(['atomic:operations' => [$op]]),
        );
    }
}
