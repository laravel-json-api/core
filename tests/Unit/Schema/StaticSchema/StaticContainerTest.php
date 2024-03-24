<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Schema\StaticSchema;

use LaravelJsonApi\Contracts\Schema\StaticSchema\StaticSchema;
use LaravelJsonApi\Core\Schema\StaticSchema\StaticContainer;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StaticContainerTest extends TestCase
{
    /**
     * @return void
     */
    public function testSchemaFor(): void
    {
        $a = $this->createSchema('App\JsonApi\V1\Post\PostSchema', 'posts');
        $b = $this->createSchema('App\JsonApi\V1\Comments\CommentSchema', 'comments');
        $c = $this->createSchema('App\JsonApi\V1\Tags\TagSchema', 'tags');

        $container = new StaticContainer([$a, $b, $c]);

        $this->assertSame([$a, $b, $c], iterator_to_array($container));
        $this->assertSame($a, $container->schemaFor('App\JsonApi\V1\Post\PostSchema'));
        $this->assertSame($b, $container->schemaFor('App\JsonApi\V1\Comments\CommentSchema'));
        $this->assertSame($c, $container->schemaFor('App\JsonApi\V1\Tags\TagSchema'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Schema does not exist: App\JsonApi\V1\Foo\FooSchema');

        $container->schemaFor('App\JsonApi\V1\Foo\FooSchema');
    }

    /**
     * @return void
     */
    public function testSchemaForType(): void
    {
        $a = $this->createSchema('App\JsonApi\V1\Post\PostSchema', 'posts');
        $b = $this->createSchema('App\JsonApi\V1\Comments\CommentSchema', 'comments');
        $c = $this->createSchema('App\JsonApi\V1\Tags\TagSchema', 'tags');

        $container = new StaticContainer([$a, $b, $c]);

        $this->assertSame([$a, $b, $c], iterator_to_array($container));
        $this->assertSame($a, $container->schemaForType('posts'));
        $this->assertSame($b, $container->schemaForType(new ResourceType('comments')));
        $this->assertSame($c, $container->schemaForType('tags'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unrecognised resource type: foobar');

        $container->schemaForType('foobar');
    }

    /**
     * @return void
     */
    public function testSchemaClassFor(): void
    {
        $container = new StaticContainer([
            $this->createSchema($a = 'App\JsonApi\V1\Post\PostSchema', 'posts'),
            $this->createSchema($b = 'App\JsonApi\V1\Comments\CommentSchema', 'comments'),
            $this->createSchema($c = 'App\JsonApi\V1\Tags\TagSchema', 'tags'),
        ]);

        $this->assertSame($a, $container->schemaClassFor('posts'));
        $this->assertSame($b, $container->schemaClassFor('comments'));
        $this->assertSame($c, $container->schemaClassFor('tags'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unrecognised resource type: blog-posts');

        $container->schemaClassFor('blog-posts');
    }

    /**
     * @return void
     */
    public function testExists(): void
    {
        $a = $this->createSchema('App\JsonApi\V1\Post\PostSchema', 'posts');
        $b = $this->createSchema('App\JsonApi\V1\Comments\CommentSchema', 'comments');
        $c = $this->createSchema('App\JsonApi\V1\Tags\TagSchema', 'tags');

        $container = new StaticContainer([$a, $b, $c]);

        foreach (['posts', 'comments', 'tags'] as $type) {
            $this->assertTrue($container->exists($type));
            $this->assertTrue($container->exists(new ResourceType($type)));
        }

        $this->assertFalse($container->exists('blog-posts'));
    }

    /**
     * @return void
     */
    public function testModelClassFor(): void
    {
        $container = new StaticContainer([
            $a = $this->createSchema('App\JsonApi\V1\Post\PostSchema', 'posts'),
            $b = $this->createSchema('App\JsonApi\V1\Comments\CommentSchema', 'comments'),
            $c = $this->createSchema('App\JsonApi\V1\Tags\TagSchema', 'tags'),
        ]);

        $a->method('getModel')->willReturn('App\Models\Post');
        $b->method('getModel')->willReturn('App\Models\Comments');
        $c->method('getModel')->willReturn('App\Models\Tags');

        $this->assertSame('App\Models\Post', $container->modelClassFor('posts'));
        $this->assertSame('App\Models\Comments', $container->modelClassFor('comments'));
        $this->assertSame('App\Models\Tags', $container->modelClassFor('tags'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unrecognised resource type: blog-posts');

        $container->modelClassFor('blog-posts');
    }

    /**
     * @return void
     */
    public function testTypeForUri(): void
    {
        $a = $this->createSchema('App\JsonApi\V1\Post\PostSchema', 'posts');
        $b = $this->createSchema('App\JsonApi\V1\Comments\CommentSchema', 'comments');
        $c = $this->createSchema('App\JsonApi\V1\Tags\TagSchema', 'tags');

        $a->expects($this->once())->method('getUriType')->willReturn('blog-posts');
        $b->expects($this->once())->method('getUriType')->willReturn('blog-comments');
        $c->expects($this->once())->method('getUriType')->willReturn('blog-tags');

        $container = new StaticContainer([$a, $b, $c]);

        $this->assertObjectEquals(new ResourceType('comments'), $container->typeForUri('blog-comments'));
        $this->assertObjectEquals(new ResourceType('tags'), $container->typeForUri('blog-tags'));
        $this->assertObjectEquals(new ResourceType('posts'), $container->typeForUri('blog-posts'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unrecognised URI type: foobar');

        $container->typeForUri('foobar');
    }

    /**
     * @return void
     */
    public function testTypes(): void
    {
        $container = new StaticContainer([
            $this->createSchema('App\JsonApi\V1\Post\PostSchema', 'posts'),
            $this->createSchema('App\JsonApi\V1\Comments\CommentSchema', 'comments'),
            $this->createSchema('App\JsonApi\V1\Tags\TagSchema', 'tags'),
        ]);

        $this->assertSame(['comments', 'posts', 'tags'], $container->types());
    }

    /**
     * @param string $schemaClass
     * @param string $type
     * @return MockObject&StaticSchema
     */
    private function createSchema(string $schemaClass, string $type): StaticSchema&MockObject
    {
        $mock = $this->createMock(StaticSchema::class);
        $mock->method('getSchemaClass')->willReturn($schemaClass);
        $mock->method('getType')->willReturn($type);

        return $mock;
    }
}