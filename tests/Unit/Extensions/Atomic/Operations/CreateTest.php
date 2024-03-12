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
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Create;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Href;
use LaravelJsonApi\Core\Extensions\Atomic\Values\OpCodeEnum;
use LaravelJsonApi\Core\Extensions\Atomic\Values\ParsedHref;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\TestCase;

class CreateTest extends TestCase
{
    /**
     * @return Create
     */
    public function testItHasHref(): Create
    {
        $op = new Create(
            $parsedHref = new ParsedHref(new Href('/posts'), new ResourceType('posts')),
            $resource = new ResourceObject(
                type: $type = new ResourceType('posts'),
                attributes: ['title' => 'Hello World!']
            ),
        );

        $this->assertSame(OpCodeEnum::Add, $op->op);
        $this->assertSame($parsedHref, $op->target);
        $this->assertNull($op->ref());
        $this->assertSame($type, $op->type());
        $this->assertSame($resource, $op->data);
        $this->assertEmpty($op->meta);
        $this->assertTrue($op->isCreating());
        $this->assertFalse($op->isUpdating());
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
     * @return Create
     */
    public function testItIsMissingHrefWithMeta(): Create
    {
        $op = new Create(
            null,
            $resource = new ResourceObject(
                type: new ResourceType('posts'),
                attributes: ['title' => 'Hello World!']
            ),
            $meta = ['foo' => 'bar'],
        );

        $this->assertSame(OpCodeEnum::Add, $op->op);
        $this->assertNull($op->target);
        $this->assertNull($op->ref());
        $this->assertSame($resource, $op->data);
        $this->assertSame($meta, $op->meta);

        return $op;
    }

    /**
     * @param Create $op
     * @return void
     * @depends testItHasHref
     */
    public function testItIsArrayableWithHref(Create $op): void
    {
        $expected = [
            'op' => $op->op->value,
            'href' => $op->target->href->value,
            'data' => $op->data->toArray(),
        ];

        $this->assertInstanceOf(Arrayable::class, $op);
        $this->assertSame($expected, $op->toArray());
    }

    /**
     * @param Create $op
     * @return void
     * @depends testItIsMissingHrefWithMeta
     */
    public function testItIsArrayableWithoutHrefAndWithMeta(Create $op): void
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
     * @param Create $op
     * @return void
     * @depends testItHasHref
     */
    public function testItIsJsonSerializableWithHref(Create $op): void
    {
        $expected = [
            'op' => $op->op,
            'href' => $op->target->href->value,
            'data' => $op->data,
        ];

        $this->assertJsonStringEqualsJsonString(
            json_encode(['atomic:operations' => [$expected]]),
            json_encode(['atomic:operations' => [$op]]),
        );
    }

    /**
     * @param Create $op
     * @return void
     * @depends testItIsMissingHrefWithMeta
     */
    public function testItIsJsonSerializableWithoutHrefAndWithMeta(Create $op): void
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
