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
use LaravelJsonApi\Core\Schema\StaticSchema\ThreadCachedStaticSchema;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ThreadCachedStaticSchemaTest extends TestCase
{
    /**
     * @var MockObject&StaticSchema
     */
    private StaticSchema&MockObject $base;

    /**
     * @var ThreadCachedStaticSchema
     */
    private ThreadCachedStaticSchema $schema;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->schema = new ThreadCachedStaticSchema(
            $this->base = $this->createMock(StaticSchema::class),
        );
    }

    /**
     * @return void
     */
    public function testType(): void
    {
        $this->base
            ->expects($this->once())
            ->method('getType')
            ->willReturn('tags');

        $this->assertSame('tags', $this->schema->getType());
        $this->assertSame('tags', $this->schema->getType());
    }

    /**
     * @return void
     */
    public function testUriType(): void
    {
        $this->base
            ->expects($this->once())
            ->method('getUriType')
            ->willReturn('blog-tags');

        $this->assertSame('blog-tags', $this->schema->getUriType());
        $this->assertSame('blog-tags', $this->schema->getUriType());
    }

    /**
     * @return void
     */
    public function testModel(): void
    {
        $this->base
            ->expects($this->once())
            ->method('getModel')
            ->willReturn($model = 'App\Models\Post');

        $this->assertSame($model, $this->schema->getModel());
        $this->assertSame($model, $this->schema->getModel());
    }

    /**
     * @return void
     */
    public function testResourceClass(): void
    {
        $this->base
            ->expects($this->once())
            ->method('getResourceClass')
            ->willReturn($class = 'App\JsonApi\V1\Tags\TagResource');

        $this->assertSame($class, $this->schema->getResourceClass());
        $this->assertSame($class, $this->schema->getResourceClass());
    }
}