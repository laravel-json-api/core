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

use LaravelJsonApi\Core\Resources\JsonApiResource;
use LaravelJsonApi\Core\Schema\StaticSchema\DefaultConventions;
use PHPUnit\Framework\TestCase;

class TestResource
{
}

class DefaultConventionsTest extends TestCase
{
    /**
     * @var DefaultConventions
     */
    private DefaultConventions $conventions;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->conventions = new DefaultConventions();
    }

    /**
     * @return array<int, array<string>>
     */
    public static function typeProvider(): array
    {
        return [
            [
                'App\JsonApi\V1\Posts\PostSchema',
                'posts',
            ],
            [
                'App\JsonApi\V1\Posts\BlogPostSchema',
                'blog-posts',
            ],
        ];
    }

    /**
     * @param string $schema
     * @param string $expected
     * @return void
     * @dataProvider typeProvider
     */
    public function testType(string $schema, string $expected): void
    {
        $this->assertSame($expected, $this->conventions->getTypeFor($schema));
    }

    /**
     * @return array<int, array<string>>
     */
    public static function uriTypeProvider(): array
    {
        return [
            ['posts', 'posts'],
            ['blogPosts', 'blog-posts'],
            ['blog_posts', 'blog-posts'],
        ];
    }

    /**
     * @param string $type
     * @param string $expected
     * @return void
     * @dataProvider uriTypeProvider
     */
    public function testUriType(string $type, string $expected): void
    {
        $this->assertSame($expected, $this->conventions->getUriTypeFor($type));
    }

    /**
     * @return array<int, array<string>>
     */
    public static function resourceClassProvider(): array
    {
        return [
            [
                'App\JsonApi\V1\Posts\PostSchema',
                JsonApiResource::class,
            ],
            [
                'LaravelJsonApi\Core\Tests\Unit\Schema\StaticSchema\TestSchema',
                TestResource::class,
            ],
        ];
    }

    /**
     * @param string $schema
     * @param string $expected
     * @return void
     * @dataProvider resourceClassProvider
     */
    public function testResourceClass(string $schema, string $expected): void
    {
        $this->assertSame($expected, $this->conventions->getResourceClassFor($schema));
    }
}