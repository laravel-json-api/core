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

namespace LaravelJsonApi\Core\Tests\Unit\Extensions\Atomic\Values;

use LaravelJsonApi\Core\Extensions\Atomic\Values\Href;
use LaravelJsonApi\Core\Extensions\Atomic\Values\ParsedHref;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Ref;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\TestCase;

class ParsedHrefTest extends TestCase
{
    /**
     * @return void
     */
    public function testItIsOnlyType(): void
    {
        $parsed = new ParsedHref(
            $href = new Href('/api/v1/posts'),
            $type = new ResourceType('posts'),
        );

        $this->assertSame($href, $parsed->href);
        $this->assertSame($type, $parsed->type);
        $this->assertNull($parsed->id);
        $this->assertNull($parsed->relationship);
        $this->assertNull($parsed->ref());
        $this->assertSame($href->value, (string) $parsed);
        $this->assertJsonStringEqualsJsonString(
            json_encode(['href' => $href]),
            json_encode(['href' => $parsed]),
        );
    }

    /**
     * @return void
     */
    public function testItIsTypeAndId(): void
    {
        $parsed = new ParsedHref(
            $href = new Href('/api/v1/posts/123'),
            $type = new ResourceType('posts'),
            $id = new ResourceId('123'),
        );

        $this->assertSame($href, $parsed->href);
        $this->assertSame($type, $parsed->type);
        $this->assertSame($id, $parsed->id);
        $this->assertNull($parsed->relationship);
        $this->assertEquals(new Ref(type: $type, id: $id), $parsed->ref());
        $this->assertSame($href->value, (string) $parsed);
        $this->assertJsonStringEqualsJsonString(
            json_encode(['href' => $href]),
            json_encode(['href' => $parsed]),
        );
    }

    /**
     * @return void
     */
    public function testItIsTypeIdAndRelationship(): void
    {
        $parsed = new ParsedHref(
            $href = new Href('/api/v1/posts/123/author'),
            $type = new ResourceType('posts'),
            $id = new ResourceId('123'),
            $fieldName = 'author',
        );

        $this->assertSame($href, $parsed->href);
        $this->assertSame($type, $parsed->type);
        $this->assertSame($id, $parsed->id);
        $this->assertSame($fieldName, $parsed->relationship);
        $this->assertEquals(new Ref(type: $type, id: $id, relationship: $fieldName), $parsed->ref());
        $this->assertSame($href->value, (string) $parsed);
        $this->assertJsonStringEqualsJsonString(
            json_encode(['href' => $href]),
            json_encode(['href' => $parsed]),
        );
    }

    /**
     * @return void
     */
    public function testItRejectsRelationshipWithoutId(): void
    {
        $this->expectException(\LogicException::class);
        new ParsedHref(new Href('/api/v1/posts/author'), new ResourceType('posts'), null, 'author');
    }
}
