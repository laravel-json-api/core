<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Query;

use LaravelJsonApi\Contracts\Schema\Relation;
use LaravelJsonApi\Contracts\Schema\Schema;
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

    public function testExistsOnSchema(): void
    {
        $schema = $this->createMock(Schema::class);
        $schema->method('isRelationship')->with('comments')->willReturn(true);
        $schema->method('relationship')->with('comments')->willReturn($relation = $this->createMock(Relation::class));
        $relation->method('isIncludePath')->willReturn(true);

        $path = new RelationshipPath('comments', 'user');

        $this->assertTrue($path->existsOnSchema($schema));
    }

    public function testExistsOnSchemaNotIncludePath(): void
    {
        $schema = $this->createMock(Schema::class);
        $schema->method('isRelationship')->with('comments')->willReturn(true);
        $schema->method('relationship')->with('comments')->willReturn($relation = $this->createMock(Relation::class));
        $relation->method('isIncludePath')->willReturn(false);

        $path = new RelationshipPath('comments', 'user');

        $this->assertFalse($path->existsOnSchema($schema));
    }

    public function testExistsOnSchemaInvalid(): void
    {
        $schema = $this->createMock(Schema::class);
        $schema->method('isRelationship')->with('comments')->willReturn(false);
        $schema->expects($this->never())->method('relationship');

        $path = new RelationshipPath('comments', 'user');

        $this->assertFalse($path->existsOnSchema($schema));
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
