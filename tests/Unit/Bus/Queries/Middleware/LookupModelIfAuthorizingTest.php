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

use LaravelJsonApi\Contracts\Store\Store;
use LaravelJsonApi\Core\Bus\Queries\FetchOne\FetchOneQuery;
use LaravelJsonApi\Core\Bus\Queries\Middleware\LookupModelIfAuthorizing;
use LaravelJsonApi\Core\Bus\Queries\Result;
use LaravelJsonApi\Core\Document\Error;
use LaravelJsonApi\Core\Document\ErrorList;
use LaravelJsonApi\Core\Document\Input\Values\ResourceId;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result as Payload;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;

class LookupModelIfAuthorizingTest extends TestCase
{
    /**
     * @var MockObject&Store
     */
    private Store&MockObject $store;

    /**
     * @var LookupModelIfAuthorizing
     */
    private LookupModelIfAuthorizing $middleware;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = new LookupModelIfAuthorizing(
            $this->store = $this->createMock(Store::class),
        );
    }

    /**
     * @return void
     */
    public function testItFindsModel(): void
    {
        $type = new ResourceType('posts');
        $id = new ResourceId('123');

        $this->store
            ->expects($this->once())
            ->method('find')
            ->with($this->identicalTo($type), $this->identicalTo($id))
            ->willReturn($model = new stdClass());

        $query = FetchOneQuery::make(null, $type)
            ->withId($id);

        $expected = Result::ok(new Payload(null, true));

        $actual = $this->middleware->handle(
            $query,
            function (FetchOneQuery $passed) use ($query, $model, $expected): Result {
                $this->assertNotSame($passed, $query);
                $this->assertSame($model, $passed->model());
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
    }

    /**
     * @return void
     */
    public function testItDoesNotFindModel(): void
    {
        $type = new ResourceType('posts');
        $id = new ResourceId('123');

        $this->store
            ->expects($this->once())
            ->method('find')
            ->with($this->identicalTo($type), $this->identicalTo($id))
            ->willReturn(null);

        $query = FetchOneQuery::make(null, $type)
            ->withId($id);

        $result = $this->middleware->handle(
            $query,
            fn() => $this->fail('Not expecting next middleware to be called.'),
        );

        $this->assertTrue($result->didFail());
        $this->assertEquals(new ErrorList(Error::make()->setStatus(404)), $result->errors());
    }

    /**
     * @return void
     */
    public function testItDoesntLookupModelIfNotAuthorizing(): void
    {
        $this->store
            ->expects($this->never())
            ->method($this->anything());

        $query = FetchOneQuery::make(null, 'posts')
            ->skipAuthorization();

        $expected = Result::ok(new Payload(null, true));

        $actual = $this->middleware->handle(
            $query,
            function (FetchOneQuery $passed) use ($query, $expected): Result {
                $this->assertSame($passed, $query);
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
    }

    /**
     * @return void
     */
    public function testItDoesntLookupModelIfModelIsAlreadySet(): void
    {
        $this->store
            ->expects($this->never())
            ->method($this->anything());

        $query = FetchOneQuery::make(null, 'posts')
            ->withModel(new stdClass());

        $expected = Result::ok(new Payload(null, true));

        $actual = $this->middleware->handle(
            $query,
            function (FetchOneQuery $passed) use ($query, $expected): Result {
                $this->assertSame($passed, $query);
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
    }
}
