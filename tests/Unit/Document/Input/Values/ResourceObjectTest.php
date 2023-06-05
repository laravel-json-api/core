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

namespace LaravelJsonApi\Core\Tests\Unit\Document\Input\Values;

use Illuminate\Contracts\Support\Arrayable;
use LaravelJsonApi\Core\Document\Input\Values\ResourceId;
use LaravelJsonApi\Core\Document\Input\Values\ResourceObject;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;
use PHPUnit\Framework\TestCase;

class ResourceObjectTest extends TestCase
{
    /**
     * @return ResourceObject
     */
    public function testItHasNoIdOrLid(): ResourceObject
    {
        $resource = new ResourceObject(
            type: $type = new ResourceType('posts'),
            attributes: $attributes = ['title' => 'My First Blog!'],
            relationships: $relations = ['author' => ['data' => null]],
            meta: $meta = ['foo' => 'bar'],
        );

        $expected = [
            'type' => $type->value,
            'attributes' => $attributes,
            'relationships' => $relations,
            'meta' => $meta,
        ];

        $this->assertSame($type, $resource->type);
        $this->assertNull($resource->id);
        $this->assertNull($resource->lid);
        $this->assertSame($attributes, $resource->attributes);
        $this->assertSame($relations, $resource->relationships);
        $this->assertSame($meta, $resource->meta);
        $this->assertInstanceOf(Arrayable::class, $resource);
        $this->assertSame($expected, $resource->toArray());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['data' => $expected]),
            json_encode(['data' => $resource]),
        );

        return $resource;
    }

    /**
     * @param ResourceObject $original
     * @return void
     * @depends testItHasNoIdOrLid
     */
    public function testItCanSetIdWithoutLid(ResourceObject $original): void
    {
        $resource = $original->withId('123');

        $expected = [
            'type' => $resource->type->value,
            'id' => '123',
            'attributes' => $resource->attributes,
            'relationships' => $resource->relationships,
            'meta' => $resource->meta,
        ];

        $this->assertNotSame($original, $resource);
        $this->assertNull($original->id);
        $this->assertObjectEquals(new ResourceId('123'), $resource->id);
        $this->assertSame($expected, $resource->toArray());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['data' => $expected]),
            json_encode(['data' => $resource]),
        );
    }

    /**
     * @return ResourceObject
     */
    public function testItHasLidWithoutId(): ResourceObject
    {
        $resource = new ResourceObject(
            type: $type = new ResourceType('posts'),
            lid: $lid = new ResourceId('123'),
            attributes: $attributes = ['title' => 'My First Blog!'],
        );

        $expected = [
            'type' => $type->value,
            'lid' => $lid->value,
            'attributes' => $attributes,
        ];

        $this->assertSame($type, $resource->type);
        $this->assertNull($resource->id);
        $this->assertSame($lid, $resource->lid);
        $this->assertSame($attributes, $resource->attributes);
        $this->assertEmpty($resource->relationships);
        $this->assertEmpty($resource->meta);
        $this->assertInstanceOf(Arrayable::class, $resource);
        $this->assertSame($expected, $resource->toArray());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['data' => $expected]),
            json_encode(['data' => $resource]),
        );

        return $resource;
    }

    /**
     * @param ResourceObject $original
     * @return void
     * @depends testItHasLidWithoutId
     */
    public function testItCanSetIdWithLid(ResourceObject $original): void
    {
        $resource = $original->withId($id = new ResourceId('345'));

        $expected = [
            'type' => $resource->type->value,
            'id' => $id->value,
            'lid' => $resource->lid->value,
            'attributes' => $resource->attributes,
        ];

        $this->assertNotSame($original, $resource);
        $this->assertNull($original->id);
        $this->assertSame($id, $resource->id);
        $this->assertSame($original->lid, $resource->lid);
        $this->assertSame($expected, $resource->toArray());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['data' => $expected]),
            json_encode(['data' => $resource]),
        );
    }

    /**
     * @return ResourceObject
     */
    public function testItHasLidAndId(): ResourceObject
    {
        $resource = new ResourceObject(
            type: $type = new ResourceType('posts'),
            id: $id = new ResourceId('123'),
            lid: $lid = new ResourceId('456'),
            relationships: $relations = ['author' => ['data' => null]],
        );

        $expected = [
            'type' => $type->value,
            'id' => $id->value,
            'lid' => $lid->value,
            'relationships' => $relations,
        ];

        $this->assertSame($type, $resource->type);
        $this->assertSame($id, $resource->id);
        $this->assertSame($lid, $resource->lid);
        $this->assertEmpty($resource->attributes);
        $this->assertSame($relations, $resource->relationships);
        $this->assertEmpty($resource->meta);
        $this->assertInstanceOf(Arrayable::class, $resource);
        $this->assertSame($expected, $resource->toArray());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['data' => $expected]),
            json_encode(['data' => $resource]),
        );

        return $resource;
    }

    /**
     * @param ResourceObject $resource
     * @return void
     * @depends testItHasLidAndId
     */
    public function testItCannotSetIdIfItAlreadyHasAnId(ResourceObject $resource): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Resource object already has an id.');
        $resource->withId('999');
    }
}
