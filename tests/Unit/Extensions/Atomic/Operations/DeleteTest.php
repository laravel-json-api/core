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
use LaravelJsonApi\Core\Document\Input\Values\ResourceId;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Delete;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Href;
use LaravelJsonApi\Core\Extensions\Atomic\Values\OpCodeEnum;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Ref;
use PHPUnit\Framework\TestCase;

class DeleteTest extends TestCase
{
    /**
     * @return Delete
     */
    public function testItHasHref(): Delete
    {
        $op = new Delete(
            $href = new Href('/posts/123'),
        );

        $this->assertSame(OpCodeEnum::Remove, $op->op);
        $this->assertSame($href, $op->target);
        $this->assertSame($href, $op->href());
        $this->assertNull($op->ref());
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
        $this->assertNull($op->href());
        $this->assertSame($ref, $op->ref());
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
