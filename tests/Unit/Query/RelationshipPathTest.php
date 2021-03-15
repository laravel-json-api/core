<?php
/*
 * Copyright 2021 Cloud Creativity Limited
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

namespace LaravelJsonApi\Core\Tests\Unit\Query;

use LaravelJsonApi\Core\Query\RelationshipPath;
use PHPUnit\Framework\TestCase;

class RelationshipPathTest extends TestCase
{

    public function testCastWithString(): void
    {
        $path = RelationshipPath::cast('comments.user');

        $this->assertEquals(['comments', 'user'], $path->names());
        $this->assertEquals(['comments', 'user'], iterator_to_array($path));
        $this->assertCount(2, $path);
        $this->assertSame('comments', $path->first());
    }

    public function testTake(): void
    {
        $path = new RelationshipPath('comments', 'user', 'image');

        $this->assertNotSame($path, $actual = $path->take(2));
        $this->assertEquals(['comments', 'user', 'image'],  $path->names());
        $this->assertEquals(['comments', 'user'], $actual->names());
    }

    public function testSkip(): void
    {
        $path = new RelationshipPath('comments', 'user', 'image');

        $this->assertNotSame($path, $actual = $path->skip(2));
        $this->assertSame(['comments', 'user', 'image'], $path->names());
        $this->assertSame(['image'], $actual->names());
        $this->assertSame(['user', 'image'], $path->skip(1)->names());
        $this->assertNull($path->skip(3));
    }

    public function testEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new RelationshipPath();
    }

    public function testEmptyString(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        RelationshipPath::fromString('');
    }
}
