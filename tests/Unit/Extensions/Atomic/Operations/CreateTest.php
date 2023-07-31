<?php
/*
 * Copyright 2023 Cloud Creativity Limited
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

namespace LaravelJsonApi\Core\Tests\Unit\Extensions\Atomic\Operations;

use Illuminate\Contracts\Support\Arrayable;
use LaravelJsonApi\Core\Document\Input\Values\ResourceObject;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Create;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Href;
use LaravelJsonApi\Core\Extensions\Atomic\Values\OpCodeEnum;
use PHPUnit\Framework\TestCase;

class CreateTest extends TestCase
{
    /**
     * @return Create
     */
    public function testItHasHref(): Create
    {
        $op = new Create(
            $href = new Href('/posts'),
            $resource = new ResourceObject(
                type: new ResourceType('posts'),
                attributes: ['title' => 'Hello World!']
            ),
        );

        $this->assertSame(OpCodeEnum::Add, $op->op);
        $this->assertSame($href, $op->target);
        $this->assertSame($href, $op->href());
        $this->assertNull($op->ref());
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
        $this->assertNull($op->href());
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
            'href' => $op->href()->value,
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
            'href' => $op->href(),
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