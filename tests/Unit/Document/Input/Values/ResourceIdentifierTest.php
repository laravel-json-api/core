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
use LaravelJsonApi\Core\Document\Input\Values\ResourceIdentifier;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;
use LaravelJsonApi\Core\Support\ContractException;
use PHPUnit\Framework\TestCase;

class ResourceIdentifierTest extends TestCase
{
    /**
     * @return ResourceIdentifier
     */
    public function testItHasLidWithoutId(): ResourceIdentifier
    {
        $identifier = new ResourceIdentifier(
            type: $type = new ResourceType('posts'),
            lid: $lid = new ResourceId('123'),
            meta: $meta = ['foo' => 'bar'],
        );

        $expected = [
            'type' => $type->value,
            'lid' => $lid->value,
            'meta' => $meta,
        ];

        $this->assertSame($type, $identifier->type);
        $this->assertNull($identifier->id);
        $this->assertSame($lid, $identifier->lid);
        $this->assertSame($meta, $identifier->meta);
        $this->assertInstanceOf(Arrayable::class, $identifier);
        $this->assertSame($expected, $identifier->toArray());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['data' => $expected]),
            json_encode(['data' => $identifier]),
        );

        return $identifier;
    }

    /**
     * @param ResourceIdentifier $original
     * @return void
     * @depends testItHasLidWithoutId
     */
    public function testItCanSetIdWithLid(ResourceIdentifier $original): void
    {
        $identifier = $original->withId($id = new ResourceId('345'));

        $expected = [
            'type' => $identifier->type->value,
            'id' => $id->value,
            'lid' => $identifier->lid->value,
            'meta' => $original->meta,
        ];

        $this->assertNotSame($original, $identifier);
        $this->assertNull($original->id);
        $this->assertSame($id, $identifier->id);
        $this->assertSame($original->lid, $identifier->lid);
        $this->assertSame($expected, $identifier->toArray());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['data' => $expected]),
            json_encode(['data' => $identifier]),
        );
    }

    /**
     * @return ResourceIdentifier
     */
    public function testItHasLidAndId(): ResourceIdentifier
    {
        $identifier = new ResourceIdentifier(
            type: $type = new ResourceType('posts'),
            id: $id = new ResourceId('123'),
            lid: $lid = new ResourceId('456'),
        );

        $expected = [
            'type' => $type->value,
            'id' => $id->value,
            'lid' => $lid->value,
        ];

        $this->assertSame($type, $identifier->type);
        $this->assertSame($id, $identifier->id);
        $this->assertSame($lid, $identifier->lid);
        $this->assertEmpty($identifier->meta);
        $this->assertInstanceOf(Arrayable::class, $identifier);
        $this->assertSame($expected, $identifier->toArray());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['data' => $expected]),
            json_encode(['data' => $identifier]),
        );

        return $identifier;
    }

    /**
     * @param ResourceIdentifier $resource
     * @return void
     * @depends testItHasLidAndId
     */
    public function testItCannotSetIdIfItAlreadyHasAnId(ResourceIdentifier $resource): void
    {
        $this->expectException(ContractException::class);
        $this->expectExceptionMessage('Resource identifier already has an id.');
        $resource->withId('999');
    }

    /**
     * @return void
     */
    public function testItMustHaveAnIdOrLid(): void
    {
        $this->expectException(ContractException::class);
        $this->expectExceptionMessage('Resource identifier must have an id or lid.');
        new ResourceIdentifier(new ResourceType('posts'));
    }
}
