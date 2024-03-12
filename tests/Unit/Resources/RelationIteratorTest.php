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

use LaravelJsonApi\Core\Resources\Concerns\ConditionallyLoadsFields;
use LaravelJsonApi\Core\Resources\JsonApiResource;
use LaravelJsonApi\Core\Resources\Relation;
use LaravelJsonApi\Core\Resources\RelationIterator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RelationIteratorTest extends TestCase
{
    use ConditionallyLoadsFields;

    /**
     * @var JsonApiResource|MockObject
     */
    private $resource;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->resource = $this->createMock(JsonApiResource::class);
    }

    public function test(): void
    {
        $model = new \stdClass();

        $this->withRelations([
            $a = new Relation($model, '/posts/123', 'author'),
            $this->when(true, $b = new Relation($model, '/posts/123', 'publishedBy')),
            $this->mergeWhen(true, [
                $c = new Relation($model, '/posts/123', 'foo'),
                $d = new Relation($model, '/posts/123', 'bar'),
            ]),
            $this->mergeWhen(true, [
                $e = new Relation($model, '/posts/123', 'baz'),
                $f = new Relation($model, '/posts/123', 'bat'),
            ]),
        ]);

        $iterator = new RelationIterator($this->resource);

        $this->assertSame([
            'author' => $a,
            'publishedBy' => $b,
            'foo' => $c,
            'bar' => $d,
            'baz' => $e,
            'bat' => $f,
        ], $iterator->all());
    }

    public function testItDoesNotSkipFields(): void
    {
        $model = new \stdClass();

        $this->withRelations([
            $a = new Relation($model, '/posts/123', 'author'),
            $this->when(false, $b = new Relation($model, '/posts/123', 'publishedBy')),
            $this->mergeWhen(false, [
                $c = new Relation($model, '/posts/123', 'foo'),
                $d = new Relation($model, '/posts/123', 'bar'),
            ]),
            $this->mergeWhen(false, [
                $e = new Relation($model, '/posts/123', 'baz'),
                $f = new Relation($model, '/posts/123', 'bat'),
            ]),
        ]);

        $iterator = new RelationIterator($this->resource);

        $this->assertSame([
            'author' => $a,
            'publishedBy' => $b,
            'foo' => $c,
            'bar' => $d,
            'baz' => $e,
            'bat' => $f,
        ], $iterator->all());
    }

    /**
     * @param array $relations
     * @return void
     */
    private function withRelations(array $relations): void
    {
        $this->resource
            ->method('relationships')
            ->with(null)
            ->willReturn($relations);
    }
}
