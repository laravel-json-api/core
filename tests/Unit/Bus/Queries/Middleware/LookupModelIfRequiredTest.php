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

use Closure;
use LaravelJsonApi\Contracts\Store\Store;
use LaravelJsonApi\Core\Bus\Queries\FetchOne\FetchOneQuery;
use LaravelJsonApi\Core\Bus\Queries\FetchRelated\FetchRelatedQuery;
use LaravelJsonApi\Core\Bus\Queries\IsIdentifiable;
use LaravelJsonApi\Core\Bus\Queries\Middleware\LookupModelIfRequired;
use LaravelJsonApi\Core\Bus\Queries\Query;
use LaravelJsonApi\Core\Bus\Queries\Result;
use LaravelJsonApi\Core\Document\Error;
use LaravelJsonApi\Core\Document\ErrorList;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result as Payload;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;

class LookupModelIfRequiredTest extends TestCase
{
    /**
     * @var MockObject&Store
     */
    private Store&MockObject $store;

    /**
     * @var LookupModelIfRequired
     */
    private LookupModelIfRequired $middleware;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = new LookupModelIfRequired(
            $this->store = $this->createMock(Store::class),
        );
    }

    /**
     * @return array<array<Closure>>
     */
    public static function modelRequiredProvider(): array
    {
        return [
            'find-one:authorize' => [
                static function (): FetchOneQuery {
                    return FetchOneQuery::make(null, 'posts')
                        ->withId('123');
                },
            ],
            'find-related:authorize' => [
                static function (): FetchRelatedQuery {
                    return FetchRelatedQuery::make(null, 'posts')
                        ->withId('123')
                        ->withFieldName('comments');
                },
            ],
            'find-related:no authorization' => [
                static function (): FetchRelatedQuery {
                    return FetchRelatedQuery::make(null, 'posts')
                        ->withId('123')
                        ->withFieldName('comments')
                        ->skipAuthorization();
                },
            ],
        ];
    }

    /**
     * @return array<array<Closure>>
     */
    public static function modelNotRequiredProvider(): array
    {
        return [
            'find-one:no authorization' => [
                static function (): FetchOneQuery {
                    return FetchOneQuery::make(null, 'posts')
                        ->withId('123')
                        ->skipAuthorization();
                },
            ],
        ];
    }

    /**
     * @param Closure $scenario
     * @return void
     * @dataProvider modelRequiredProvider
     */
    public function testItFindsModel(Closure $scenario): void
    {
        /** @var Query&IsIdentifiable $query */
        $query = $scenario();
        $type = $query->type();
        $id = $query->idOrFail();

        $this->store
            ->expects($this->once())
            ->method('find')
            ->with($this->identicalTo($type), $this->identicalTo($id))
            ->willReturn($model = new stdClass());

        $expected = Result::ok(new Payload(null, true));

        $actual = $this->middleware->handle(
            $query,
            function (Query&IsIdentifiable $passed) use ($query, $model, $expected): Result {
                $this->assertNotSame($passed, $query);
                $this->assertSame($model, $passed->model());
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
    }

    /**
     * @param Closure $scenario
     * @return void
     * @dataProvider modelRequiredProvider
     */
    public function testItDoesNotFindModelIfAlreadySet(Closure $scenario): void
    {
        /** @var Query&IsIdentifiable $query */
        $query = $scenario();
        /** @var Query&IsIdentifiable $query */
        $query = $query->withModel(new \stdClass());

        $this->store
            ->expects($this->never())
            ->method($this->anything());

        $expected = Result::ok(new Payload(null, true));

        $actual = $this->middleware->handle(
            $query,
            function (Query $passed) use ($query, $expected): Result {
                $this->assertSame($passed, $query);
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
    }

    /**
     * @param Closure $scenario
     * @return void
     * @dataProvider modelRequiredProvider
     */
    public function testItDoesNotFindModel(Closure $scenario): void
    {
        /** @var Query&IsIdentifiable $query */
        $query = $scenario();
        $type = $query->type();
        $id = $query->idOrFail();

        $this->store
            ->expects($this->once())
            ->method('find')
            ->with($this->identicalTo($type), $this->identicalTo($id))
            ->willReturn(null);

        $result = $this->middleware->handle(
            $query,
            fn() => $this->fail('Not expecting next middleware to be called.'),
        );

        $this->assertTrue($result->didFail());
        $this->assertEquals(new ErrorList(Error::make()->setStatus(404)), $result->errors());
    }

    /**
     * @param Closure $scenario
     * @return void
     * @dataProvider modelNotRequiredProvider
     */
    public function testItDoesntLookupModelIfNotRequired(Closure $scenario): void
    {
        $this->store
            ->expects($this->never())
            ->method($this->anything());

        /** @var Query&IsIdentifiable $query */
        $query = $scenario();

        $expected = Result::ok(new Payload(null, true));

        $actual = $this->middleware->handle(
            $query,
            function (Query $passed) use ($query, $expected): Result {
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
            function (Query $passed) use ($query, $expected): Result {
                $this->assertSame($passed, $query);
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
    }
}
