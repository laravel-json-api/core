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

use LaravelJsonApi\Core\Query\IncludePaths;
use LaravelJsonApi\Core\Query\RelationshipPath;
use PHPUnit\Framework\TestCase;

class IncludePathsTest extends TestCase
{

    public function testCastWithString(): IncludePaths
    {
        $paths = IncludePaths::cast($value = 'author.profile,comments,tags');

        $this->assertEquals($expected = [
            new RelationshipPath('author', 'profile'),
            new RelationshipPath('comments'),
            new RelationshipPath('tags'),
        ], $paths->all());

        $this->assertEquals(collect($expected), $paths->collect());

        $this->assertSame($value, (string) $paths);

        return $paths;
    }

    public function testCastWithArray(): void
    {
        $paths = IncludePaths::cast($value = ['author.profile', 'comments', 'tags']);

        $this->assertEquals([
            new RelationshipPath('author', 'profile'),
            new RelationshipPath('comments'),
            new RelationshipPath('tags'),
        ], $paths->all());

        $this->assertSame($value, $paths->toArray());
    }

    /**
     * @param IncludePaths $expected
     * @depends testCastWithString
     */
    public function testCastWithEnumerable(IncludePaths $expected): void
    {
        $paths = IncludePaths::cast(collect($expected->all()));

        $this->assertEquals($expected, $paths);
    }

    public function testCastWithPath(): void
    {
        $path = new RelationshipPath('author', 'profile');
        $paths = IncludePaths::cast($path);

        $this->assertEquals([$path], $paths->all());
    }

    public function testCastWithNull(): void
    {
        $paths = IncludePaths::cast(null);

        $this->assertEmpty($paths->all());
        $this->assertTrue($paths->isEmpty());
        $this->assertFalse($paths->isNotEmpty());
    }

    /**
     * @param IncludePaths $expected
     * @depends testCastWithString
     */
    public function testNullable(IncludePaths $expected): void
    {
        $this->assertNull(IncludePaths::nullable(null));
        $this->assertEquals(new IncludePaths(), IncludePaths::nullable([]));
        $this->assertEquals($expected, IncludePaths::nullable($expected->toString()));
    }

    /**
     * @param IncludePaths $paths
     * @depends testCastWithString
     */
    public function testCountAndNotEmpty(IncludePaths $paths): void
    {
        $this->assertCount(3, $paths);
        $this->assertTrue($paths->isNotEmpty());
        $this->assertFalse($paths->isEmpty());
    }

    /**
     * @param IncludePaths $paths
     * @depends testCastWithString
     */
    public function testIterator(IncludePaths $paths): void
    {
        $this->assertEquals($paths->all(), iterator_to_array($paths));
    }

    public function testSkip(): void
    {
        $paths = IncludePaths::cast($value = [
            'author.profile',
            'comments.user.image',
            'tags',
        ]);

        $actual = $paths->skip(1);

        $this->assertNotSame($paths, $actual);
        $this->assertEquals($value, $paths->toArray());

        $this->assertEquals([
            'profile',
            'user.image',
        ], $actual->toArray());

        $this->assertEquals(['image'], $paths->skip(2)->toArray());
    }
}
