<?php
/*
 * Copyright 2022 Cloud Creativity Limited
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
