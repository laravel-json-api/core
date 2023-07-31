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

use LaravelJsonApi\Core\Document\Input\Values\ListOfResourceIdentifiers;
use LaravelJsonApi\Core\Document\Input\Values\ResourceId;
use LaravelJsonApi\Core\Document\Input\Values\ResourceIdentifier;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\UpdateToMany;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Href;
use LaravelJsonApi\Core\Extensions\Atomic\Values\OpCodeEnum;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Ref;
use PHPUnit\Framework\TestCase;

class UpdateToManyTest extends TestCase
{
    /**
     * @return void
     */
    public function testItIsAddWithHref(): void
    {
        $op = new UpdateToMany(
            $code = OpCodeEnum::Add,
            $href = new Href('/posts/123/relationships/tags'),
            $identifiers = new ListOfResourceIdentifiers(
                new ResourceIdentifier(new ResourceType('tags'), new ResourceId('123')),
            ),
            $meta = ['foo' => 'bar'],
        );

        $this->assertSame($code, $op->op);
        $this->assertSame($href, $op->target);
        $this->assertSame($href, $op->href());
        $this->assertNull($op->ref());
        $this->assertSame($identifiers, $op->data);
        $this->assertSame($meta, $op->meta);
        $this->assertFalse($op->isCreating());
        $this->assertFalse($op->isUpdating());
        $this->assertFalse($op->isCreatingOrUpdating());
        $this->assertFalse($op->isDeleting());
        $this->assertSame('tags', $op->getFieldName());
        $this->assertFalse($op->isUpdatingRelationship());
        $this->assertTrue($op->isAttachingRelationship());
        $this->assertFalse($op->isDetachingRelationship());
        $this->assertTrue($op->isModifyingRelationship());
        $this->assertSame([
            'op' => $code->value,
            'href' => $href->value,
            'data' => $identifiers->toArray(),
            'meta' => $meta,
        ], $op->toArray());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['op' => $code, 'href' => $href, 'data' => $identifiers, 'meta' => $meta]),
            json_encode($op),
        );
    }

    /**
     * @return void
     */
    public function testItIsAddWithRef(): void
    {
        $op = new UpdateToMany(
            $code = OpCodeEnum::Add,
            $ref = new Ref(
                type: new ResourceType('posts'),
                id: new ResourceId('123'),
                relationship: 'tags',
            ),
            $identifiers = new ListOfResourceIdentifiers(
                new ResourceIdentifier(new ResourceType('tags'), new ResourceId('456')),
            ),
            $meta = ['foo' => 'bar'],
        );

        $this->assertSame($code, $op->op);
        $this->assertSame($ref, $op->target);
        $this->assertNull($op->href());
        $this->assertSame($ref, $op->ref());
        $this->assertSame($identifiers, $op->data);
        $this->assertSame($meta, $op->meta);
        $this->assertFalse($op->isCreating());
        $this->assertFalse($op->isUpdating());
        $this->assertFalse($op->isCreatingOrUpdating());
        $this->assertFalse($op->isDeleting());
        $this->assertSame('tags', $op->getFieldName());
        $this->assertFalse($op->isUpdatingRelationship());
        $this->assertTrue($op->isAttachingRelationship());
        $this->assertFalse($op->isDetachingRelationship());
        $this->assertTrue($op->isModifyingRelationship());
        $this->assertSame([
            'op' => $code->value,
            'ref' => $ref->toArray(),
            'data' => $identifiers->toArray(),
            'meta' => $meta,
        ], $op->toArray());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['op' => $code, 'ref' => $ref, 'data' => $identifiers, 'meta' => $meta]),
            json_encode($op),
        );
    }

    /**
     * @return void
     */
    public function testItIsUpdateWithHref(): void
    {
        $op = new UpdateToMany(
            $code = OpCodeEnum::Update,
            $href = new Href('/posts/123/relationships/tags'),
            $identifiers = new ListOfResourceIdentifiers(),
        );

        $this->assertSame($code, $op->op);
        $this->assertSame($href, $op->target);
        $this->assertSame($href, $op->href());
        $this->assertNull($op->ref());
        $this->assertSame($identifiers, $op->data);
        $this->assertEmpty($op->meta);
        $this->assertFalse($op->isCreating());
        $this->assertFalse($op->isUpdating());
        $this->assertFalse($op->isCreatingOrUpdating());
        $this->assertFalse($op->isDeleting());
        $this->assertSame('tags', $op->getFieldName());
        $this->assertTrue($op->isUpdatingRelationship());
        $this->assertFalse($op->isAttachingRelationship());
        $this->assertFalse($op->isDetachingRelationship());
        $this->assertTrue($op->isModifyingRelationship());
        $this->assertSame([
            'op' => $code->value,
            'href' => $href->value,
            'data' => $identifiers->toArray(),
        ], $op->toArray());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['op' => $code, 'href' => $href, 'data' => $identifiers]),
            json_encode($op),
        );
    }

    /**
     * @return void
     */
    public function testItIsUpdateWithRef(): void
    {
        $op = new UpdateToMany(
            $code = OpCodeEnum::Update,
            $ref = new Ref(
                type: new ResourceType('posts'),
                id: new ResourceId('123'),
                relationship: 'tags',
            ),
            $identifiers = new ListOfResourceIdentifiers(),
        );

        $this->assertSame($code, $op->op);
        $this->assertSame($ref, $op->target);
        $this->assertNull($op->href());
        $this->assertSame($ref, $op->ref());
        $this->assertSame($identifiers, $op->data);
        $this->assertEmpty($op->meta);
        $this->assertFalse($op->isCreating());
        $this->assertFalse($op->isUpdating());
        $this->assertFalse($op->isCreatingOrUpdating());
        $this->assertFalse($op->isDeleting());
        $this->assertSame('tags', $op->getFieldName());
        $this->assertTrue($op->isUpdatingRelationship());
        $this->assertFalse($op->isAttachingRelationship());
        $this->assertFalse($op->isDetachingRelationship());
        $this->assertTrue($op->isModifyingRelationship());
        $this->assertSame([
            'op' => $code->value,
            'ref' => $ref->toArray(),
            'data' => $identifiers->toArray(),
        ], $op->toArray());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['op' => $code, 'ref' => $ref, 'data' => $identifiers]),
            json_encode($op),
        );
    }

    /**
     * @return void
     */
    public function testItIsRemoveWithHref(): void
    {
        $op = new UpdateToMany(
            $code = OpCodeEnum::Remove,
            $href = new Href('/posts/123/relationships/tags'),
            $identifiers = new ListOfResourceIdentifiers(
                new ResourceIdentifier(new ResourceType('tags'), new ResourceId('123')),
            ),
        );

        $this->assertSame($code, $op->op);
        $this->assertSame($href, $op->target);
        $this->assertSame($href, $op->href());
        $this->assertNull($op->ref());
        $this->assertSame($identifiers, $op->data);
        $this->assertEmpty($op->meta);
        $this->assertFalse($op->isCreating());
        $this->assertFalse($op->isUpdating());
        $this->assertFalse($op->isCreatingOrUpdating());
        $this->assertFalse($op->isDeleting());
        $this->assertSame('tags', $op->getFieldName());
        $this->assertFalse($op->isUpdatingRelationship());
        $this->assertFalse($op->isAttachingRelationship());
        $this->assertTrue($op->isDetachingRelationship());
        $this->assertTrue($op->isModifyingRelationship());
        $this->assertSame([
            'op' => $code->value,
            'href' => $href->value,
            'data' => $identifiers->toArray(),
        ], $op->toArray());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['op' => $code, 'href' => $href, 'data' => $identifiers]),
            json_encode($op),
        );
    }

    /**
     * @return void
     */
    public function testItIsRemoveWithRef(): void
    {
        $op = new UpdateToMany(
            $code = OpCodeEnum::Remove,
            $ref = new Ref(
                type: new ResourceType('posts'),
                id: new ResourceId('123'),
                relationship: 'tags',
            ),
            $identifiers = new ListOfResourceIdentifiers(
                new ResourceIdentifier(new ResourceType('tags'), new ResourceId('456')),
            ),
        );

        $this->assertSame($code, $op->op);
        $this->assertSame($ref, $op->target);
        $this->assertNull($op->href());
        $this->assertSame($ref, $op->ref());
        $this->assertSame($identifiers, $op->data);
        $this->assertEmpty($op->meta);
        $this->assertFalse($op->isCreating());
        $this->assertFalse($op->isUpdating());
        $this->assertFalse($op->isCreatingOrUpdating());
        $this->assertFalse($op->isDeleting());
        $this->assertSame('tags', $op->getFieldName());
        $this->assertFalse($op->isUpdatingRelationship());
        $this->assertFalse($op->isAttachingRelationship());
        $this->assertTrue($op->isDetachingRelationship());
        $this->assertTrue($op->isModifyingRelationship());
        $this->assertSame([
            'op' => $code->value,
            'ref' => $ref->toArray(),
            'data' => $identifiers->toArray(),
        ], $op->toArray());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['op' => $code, 'ref' => $ref, 'data' => $identifiers]),
            json_encode($op),
        );
    }
}