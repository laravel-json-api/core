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
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Update;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Href;
use LaravelJsonApi\Core\Extensions\Atomic\Values\OpCodeEnum;
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
            $href = new Href('/posts/123'),
            $resource = new ResourceObject(
                type: new ResourceType('posts'),
                id: new ResourceId('123'),
                attributes: ['title' => 'Hello World!']
            ),
        );

        $this->assertSame(OpCodeEnum::Update, $op->op);
        $this->assertSame($href, $op->target);
        $this->assertSame($href, $op->href());
        $this->assertNull($op->ref());
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
                type: new ResourceType('posts'),
                id: new ResourceId('123'),
                attributes: ['title' => 'Hello World!']
            ),
            $meta = ['foo' => 'bar'],
        );

        $this->assertSame(OpCodeEnum::Update, $op->op);
        $this->assertNull($op->target);
        $this->assertNull($op->href());
        $this->assertNull($op->ref());
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
