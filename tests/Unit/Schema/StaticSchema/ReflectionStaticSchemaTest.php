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

use LaravelJsonApi\Contracts\Schema\StaticSchema\ServerConventions;
use LaravelJsonApi\Core\Schema\Attributes\Model;
use LaravelJsonApi\Core\Schema\Attributes\ResourceClass;
use LaravelJsonApi\Core\Schema\Attributes\Type;
use LaravelJsonApi\Core\Schema\Schema;
use LaravelJsonApi\Core\Schema\StaticSchema\ReflectionStaticSchema;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[Model('App\Models\Post')]
class PostSchema extends Schema
{
    public function __construct()
    {

    }

    public function fields(): iterable
    {
        return [];
    }
}

#[Model('App\Models\Tag')]
#[Type(type: 'tags', uri: 'blog-tags')]
#[ResourceClass('App\JsonApi\Tags\TagResource')]
class TagSchema extends Schema
{
    public function __construct()
    {

    }

    public function fields(): iterable
    {
        return [];
    }
}

class ReflectionStaticSchemaTest extends TestCase
{
    /**
     * @var ServerConventions&MockObject
     */
    private ServerConventions&MockObject $conventions;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->conventions = $this->createMock(ServerConventions::class);
    }

    /**
     * @return void
     */
    public function testDefaults(): void
    {
        $this->conventions
            ->method('getTypeFor')
            ->with(PostSchema::class)
            ->willReturn('blog-posts');

        $this->conventions
            ->method('getUriTypeFor')
            ->with('blog-posts')
            ->willReturn('blog_posts');

        $this->conventions
            ->method('getResourceClassFor')
            ->with(PostSchema::class)
            ->willReturn('App\JsonApi\MyResource');

        $schema = new ReflectionStaticSchema(PostSchema::class, $this->conventions);

        $this->assertSame(PostSchema::class, $schema->getSchemaClass());
        $this->assertSame('App\Models\Post', $schema->getModel());
        $this->assertSame('blog-posts', $schema->getType());
        $this->assertSame('blog_posts', $schema->getUriType());
        $this->assertSame('App\JsonApi\MyResource', $schema->getResourceClass());
    }

    /**
     * @return void
     */
    public function testCustomised(): void
    {
        $this->conventions
            ->expects($this->never())
            ->method($this->anything());

        $schema = new ReflectionStaticSchema(TagSchema::class, $this->conventions);

        $this->assertSame(TagSchema::class, $schema->getSchemaClass());
        $this->assertSame('App\Models\Tag', $schema->getModel());
        $this->assertSame('tags', $schema->getType());
        $this->assertSame('blog-tags', $schema->getUriType());
        $this->assertSame('App\JsonApi\Tags\TagResource', $schema->getResourceClass());
    }
}