<?php
/*
 * Copyright 2023 Cloud Creativity Limited
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

namespace LaravelJsonApi\Core\Tests\Unit\Bus\Queries\Middleware;

use LaravelJsonApi\Contracts\Query\QueryParameters;
use LaravelJsonApi\Contracts\Resources\Factory;
use LaravelJsonApi\Core\Bus\Queries\FetchOne\FetchOneQuery;
use LaravelJsonApi\Core\Bus\Queries\Middleware\LookupResourceIdIfNotSet;
use LaravelJsonApi\Core\Bus\Queries\Query;
use LaravelJsonApi\Core\Bus\Queries\Result;
use LaravelJsonApi\Core\Document\Input\Values\ResourceId;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result as Payload;
use LaravelJsonApi\Core\Resources\JsonApiResource;
use LaravelJsonApi\Core\Store\ModelKey;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LookupResourceIdIfNotSetTest extends TestCase
{
    /**
     * @var MockObject&Factory
     */
    private Factory&MockObject $factory;

    /**
     * @var LookupResourceIdIfNotSet
     */
    private LookupResourceIdIfNotSet $middleware;

    /**
     * @var Result
     */
    private Result $expected;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = new LookupResourceIdIfNotSet(
            $this->factory = $this->createMock(Factory::class),
        );

        $this->expected = Result::ok(
            new Payload(null, true),
            $this->createMock(QueryParameters::class),
        );
    }

    /**
     * @return void
     */
    public function testItSetsResourceId(): void
    {
        $query = $this->createQuery(type: 'blog-posts', model: $model = new \stdClass());
        $query
            ->expects($this->once())
            ->method('withId')
            ->with('123')
            ->willReturn($queryWithId = $this->createMock(FetchOneQuery::class));

        $this->willCreateResource($model, 'blog-posts', '123');

        $actual = $this->middleware->handle($query, function ($passed) use ($queryWithId): Result {
            $this->assertSame($queryWithId, $passed);
            return $this->expected;
        });

        $this->assertSame($this->expected, $actual);
    }

    /**
     * @return void
     */
    public function testItThrowsUnexpectedResourceType(): void
    {
        $query = $this->createQuery(type: 'comments', model: $model = new \stdClass());
        $query->expects($this->never())->method('withId');

        $this->willCreateResource($model, 'tags', '456');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Expecting resource type "comments" but provided model is of type "tags".');

        $this->middleware->handle(
            $query,
            fn () => $this->fail('Next middleware unexpectedly called.'),
        );
    }

    /**
     * @return void
     */
    public function testItSkipsQueryWithResourceId(): void
    {
        $query = $this->createQuery(id: '999');

        $this->factory
            ->expects($this->never())
            ->method($this->anything());

        $actual = $this->middleware->handle($query, function ($passed) use ($query): Result {
            $this->assertSame($query, $passed);
            return $this->expected;
        });

        $this->assertSame($this->expected, $actual);
    }

    /**
     * @return void
     */
    public function testItSkipsQueryWithModelKey(): void
    {
        $query = $this->createQuery(modelKey: 999);

        $this->factory
            ->expects($this->never())
            ->method($this->anything());

        $actual = $this->middleware->handle($query, function ($passed) use ($query): Result {
            $this->assertSame($query, $passed);
            return $this->expected;
        });

        $this->assertSame($this->expected, $actual);
    }

    /**
     * @param string $type
     * @param string|null $id
     * @param string|int|null $modelKey
     * @param object $model
     * @return MockObject&Query
     */
    private function createQuery(
        string $type = 'posts',
        string $id = null,
        string|int $modelKey = null,
        object $model = new \stdClass(),
    ): Query&MockObject {
        $query = $this->createMock(FetchOneQuery::class);
        $query->method('type')->willReturn(new ResourceType($type));
        $query->method('id')->willReturn(ResourceId::nullable($id));
        $query->method('modelKey')->willReturn(ModelKey::nullable($modelKey));
        $query->method('modelOrFail')->willReturn($model);

        return $query;
    }

    /**
     * @param object $model
     * @param string $type
     * @param string $id
     * @return void
     */
    private function willCreateResource(object $model, string $type, string $id): void
    {
        $resource = $this->createMock(JsonApiResource::class);
        $resource->method('type')->willReturn($type);
        $resource->method('id')->willReturn($id);

        $this->factory
            ->expects($this->once())
            ->method('createResource')
            ->with($this->identicalTo($model))
            ->willReturn($resource);
    }
}
