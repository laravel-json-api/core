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

use LaravelJsonApi\Core\Document\Input\Values\ListOfResourceIdentifiers;
use LaravelJsonApi\Core\Document\Input\Values\ResourceIdentifier;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\UpdateToMany;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Href;
use LaravelJsonApi\Core\Extensions\Atomic\Values\OpCodeEnum;
use LaravelJsonApi\Core\Extensions\Atomic\Values\ParsedHref;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Ref;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;
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
            $parsedHref = new ParsedHref(
                $href = new Href('/posts/123/relationships/tags'),
                $type = new ResourceType('posts'),
                $id = new ResourceId('id'),
                $relationship = 'tags',
            ),
            $identifiers = new ListOfResourceIdentifiers(
                new ResourceIdentifier(new ResourceType('tags'), new ResourceId('123')),
            ),
            $meta = ['foo' => 'bar'],
        );

        $this->assertSame($code, $op->op);
        $this->assertSame($parsedHref, $op->target);
        $this->assertSame($href, $op->href());
        $this->assertEquals(new Ref(type: $type, id: $id, relationship: $relationship), $op->ref());
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
            $parsedHref = new ParsedHref(
                $href = new Href('/posts/123/relationships/tags'),
                $type = new ResourceType('posts'),
                $id = new ResourceId('id'),
                $relationship = 'tags',
            ),
            $identifiers = new ListOfResourceIdentifiers(),
        );

        $this->assertSame($code, $op->op);
        $this->assertSame($parsedHref, $op->target);
        $this->assertSame($href, $op->href());
        $this->assertEquals(new Ref(type: $type, id: $id, relationship: $relationship), $op->ref());
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
            $parsedHref = new ParsedHref(
                $href = new Href('/posts/123/relationships/tags'),
                $type = new ResourceType('posts'),
                $id = new ResourceId('id'),
                $relationship = 'tags',
            ),
            $identifiers = new ListOfResourceIdentifiers(
                new ResourceIdentifier(new ResourceType('tags'), new ResourceId('123')),
            ),
        );

        $this->assertSame($code, $op->op);
        $this->assertSame($parsedHref, $op->target);
        $this->assertSame($href, $op->href());
        $this->assertEquals(new Ref(type: $type, id: $id, relationship: $relationship), $op->ref());
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
