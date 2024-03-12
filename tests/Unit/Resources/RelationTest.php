<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Resources;

use LaravelJsonApi\Core\Document\Link;
use LaravelJsonApi\Core\Document\Links;
use LaravelJsonApi\Core\Resources\Relation;
use PHPUnit\Framework\TestCase;

class RelationTest extends TestCase
{

    /**
     * @var object
     */
    private object $model;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->model = (object) [
            'author' => (object) [
                'name' => 'John Doe',
            ],
        ];
    }

    public function test(): void
    {
        $relation = new Relation(
            $this->model,
            'http://localhost/api/v1/posts/1',
            'author',
        );

        $this->assertFalse($relation->showData());
        $this->assertEquals($relation->data(), $this->model->author);
        $this->assertEquals(null, $relation->meta());
        $this->assertEquals(new Links(
            new Link('self', 'http://localhost/api/v1/posts/1/relationships/author'),
            new Link('related', 'http://localhost/api/v1/posts/1/author'),
        ), $relation->links());
    }

    public function testKeyName(): void
    {
        $relation = new Relation(
            $this->model,
            'http://localhost/api/v1/posts/1',
            'user',
            'author',
        );

        $this->assertEquals($relation->data(), $this->model->author);
    }

    public function testDataAsClosure(): void
    {
        $relation = new Relation(
            $this->model,
            'http://localhost/api/v1/posts/1',
            'author',
        );

        $expected = (object) ['foo' => 'bar'];

        $this->assertSame($relation, $relation->withData(fn() => $expected));
        $this->assertSame($expected, $relation->data());
    }

    public function testShowData(): void
    {
        $relation = new Relation(
            $this->model,
            'http://localhost/api/v1/posts/1',
            'author',
        );

        $this->assertSame($relation, $relation->alwaysShowData());
        $this->assertTrue($relation->showData());
    }

    public function testWithoutSelfLink(): void
    {
        $relation = new Relation(
            $this->model,
            'http://localhost/api/v1/posts/1',
            'author',
        );

        $this->assertSame($relation, $relation->withoutSelfLink());

        $this->assertEquals(new Links(
            new Link('related', 'http://localhost/api/v1/posts/1/author'),
        ), $relation->links());
    }

    public function testWithoutRelatedLink(): void
    {
        $relation = new Relation(
            $this->model,
            'http://localhost/api/v1/posts/1',
            'author',
        );

        $this->assertSame($relation, $relation->withoutRelatedLink());

        $this->assertEquals(new Links(
            new Link('self', 'http://localhost/api/v1/posts/1/relationships/author'),
        ), $relation->links());
    }

    public function testOnlySelfLink(): void
    {
        $relation = new Relation(
            $this->model,
            'http://localhost/api/v1/posts/1',
            'author',
        );

        $this->assertSame($relation, $relation->onlySelfLink());

        $this->assertEquals(new Links(
            new Link('self', 'http://localhost/api/v1/posts/1/relationships/author'),
        ), $relation->links());
    }

    public function testOnlyRelatedLink(): void
    {
        $relation = new Relation(
            $this->model,
            'http://localhost/api/v1/posts/1',
            'author',
        );

        $this->assertSame($relation, $relation->onlyRelatedLink());

        $this->assertEquals(new Links(
            new Link('related', 'http://localhost/api/v1/posts/1/author'),
        ), $relation->links());
    }

    public function testWithoutLinks(): void
    {
        $relation = new Relation(
            $this->model,
            'http://localhost/api/v1/posts/1',
            'author',
        );

        $this->assertSame($relation, $relation->withoutLinks());
        $this->assertEquals(new Links(), $relation->links());
    }

    public function testWithUriFieldName(): void
    {
        $relation = new Relation(
            $this->model,
            'http://localhost/api/v1/posts/1',
            'author',
        );

        $this->assertSame($relation, $relation->withUriFieldName('blog-author'));

        $this->assertEquals(new Links(
            new Link('self', 'http://localhost/api/v1/posts/1/relationships/blog-author'),
            new Link('related', 'http://localhost/api/v1/posts/1/blog-author'),
        ), $relation->links());
    }

    public function testRetainFieldName(): void
    {
        $model = (object) [
            'blogAuthor' => $this->model->author,
        ];

        $relation = new Relation(
            $model,
            'http://localhost/api/v1/posts/1',
            'blogAuthor',
        );

        $relation->retainFieldName();

        $this->assertEquals(new Links(
            new Link('self', 'http://localhost/api/v1/posts/1/relationships/blogAuthor'),
            new Link('related', 'http://localhost/api/v1/posts/1/blogAuthor'),
        ), $relation->links());
    }

    public function testWithMeta(): void
    {
        $relation = new Relation(
            $this->model,
            'http://localhost/api/v1/posts/1',
            'author',
        );

        $this->assertSame($relation, $relation->withMeta(['foo' => 'bar']));
        $this->assertSame(['foo' => 'bar'], $relation->meta());
    }

    public function testWithMetaClosure(): void
    {
        $relation = new Relation(
            $this->model,
            'http://localhost/api/v1/posts/1',
            'author',
        );

        $this->assertSame($relation, $relation->withMeta(fn() => ['foo' => 'bar']));
        $this->assertSame(['foo' => 'bar'], $relation->meta());
    }
}
