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
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Delete;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Href;
use LaravelJsonApi\Core\Extensions\Atomic\Values\OpCodeEnum;
use LaravelJsonApi\Core\Extensions\Atomic\Values\ParsedHref;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Ref;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\TestCase;

class DeleteTest extends TestCase
{
    /**
     * @return Delete
     */
    public function testItHasHref(): Delete
    {
        $op = new Delete(
            $parsedHref = new ParsedHref(
                $href = new Href('/posts/123'),
                $type = new ResourceType('posts'),
                $id = new ResourceId('123')
            ),
        );

        $this->assertSame(OpCodeEnum::Remove, $op->op);
        $this->assertSame($parsedHref, $op->target);
        $this->assertEquals(new Ref(type: $type, id: $id), $op->ref());
        $this->assertSame($href, $op->href());
        $this->assertEmpty($op->meta);
        $this->assertFalse($op->isCreating());
        $this->assertFalse($op->isUpdating());
        $this->assertFalse($op->isCreatingOrUpdating());
        $this->assertTrue($op->isDeleting());
        $this->assertNull($op->getFieldName());
        $this->assertFalse($op->isUpdatingRelationship());
        $this->assertFalse($op->isAttachingRelationship());
        $this->assertFalse($op->isDetachingRelationship());
        $this->assertFalse($op->isModifyingRelationship());

        return $op;
    }

    /**
     * @return Delete
     */
    public function testItHasRef(): Delete
    {
        $op = new Delete(
            $ref = new Ref(new ResourceType('posts'), new ResourceId('123')),
            $meta = ['foo' => 'bar'],
        );

        $this->assertSame(OpCodeEnum::Remove, $op->op);
        $this->assertSame($ref, $op->target);
        $this->assertSame($ref, $op->ref());
        $this->assertNull($op->href());
        $this->assertSame($meta, $op->meta);

        return $op;
    }

    /**
     * @param Delete $op
     * @return void
     * @depends testItHasHref
     */
    public function testItIsArrayableWithHref(Delete $op): void
    {
        $expected = [
            'op' => $op->op->value,
            'href' => $op->href()->value,
        ];

        $this->assertInstanceOf(Arrayable::class, $op);
        $this->assertSame($expected, $op->toArray());
    }

    /**
     * @param Delete $op
     * @return void
     * @depends testItHasRef
     */
    public function testItIsArrayableWithRef(Delete $op): void
    {
        $expected = [
            'op' => $op->op->value,
            'ref' => $op->ref()->toArray(),
            'meta' => $op->meta,
        ];

        $this->assertInstanceOf(Arrayable::class, $op);
        $this->assertSame($expected, $op->toArray());
    }

    /**
     * @param Delete $op
     * @return void
     * @depends testItHasHref
     */
    public function testItIsJsonSerializableWithHref(Delete $op): void
    {
        $expected = [
            'op' => $op->op,
            'href' => $op->href(),
        ];

        $this->assertJsonStringEqualsJsonString(
            json_encode(['atomic:operations' => [$expected]]),
            json_encode(['atomic:operations' => [$op]]),
        );
    }

    /**
     * @param Delete $op
     * @return void
     * @depends testItHasRef
     */
    public function testItIsJsonSerializableWithRef(Delete $op): void
    {
        $expected = [
            'op' => $op->op,
            'ref' => $op->ref(),
            'meta' => $op->meta,
        ];

        $this->assertJsonStringEqualsJsonString(
            json_encode(['atomic:operations' => [$expected]]),
            json_encode(['atomic:operations' => [$op]]),
        );
    }
}
