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

namespace LaravelJsonApi\Core\Tests\Unit\Bus\Queries\FetchOne\Middleware;

use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer;
use LaravelJsonApi\Contracts\Auth\Container as AuthorizerContainer;
use LaravelJsonApi\Contracts\Query\QueryParameters;
use LaravelJsonApi\Core\Bus\Queries\FetchOne\FetchOneQuery;
use LaravelJsonApi\Core\Bus\Queries\FetchOne\Middleware\AuthorizeFetchOneQuery;
use LaravelJsonApi\Core\Bus\Queries\Result;
use LaravelJsonApi\Core\Document\Error;
use LaravelJsonApi\Core\Document\ErrorList;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result as Payload;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AuthorizeFetchOneQueryTest extends TestCase
{
    /**
     * @var ResourceType
     */
    private ResourceType $type;

    /**
     * @var Authorizer&MockObject
     */
    private Authorizer&MockObject $authorizer;

    /**
     * @var AuthorizeFetchOneQuery
     */
    private AuthorizeFetchOneQuery $middleware;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->type = new ResourceType('posts');

        $authorizers = $this->createMock(AuthorizerContainer::class);
        $authorizers
            ->method('authorizerFor')
            ->with($this->identicalTo($this->type))
            ->willReturn($this->authorizer = $this->createMock(Authorizer::class));

        $this->middleware = new AuthorizeFetchOneQuery(
            $authorizers,
        );
    }

    /**
     * @return void
     */
    public function testItPassesAuthorizationWithRequest(): void
    {
        $request = $this->createMock(Request::class);

        $query = FetchOneQuery::make($request, $this->type)
            ->withModel($model = new \stdClass());

        $this->authorizer
            ->expects($this->once())
            ->method('show')
            ->with($this->identicalTo($request), $this->identicalTo($model))
            ->willReturn(true);

        $this->authorizer
            ->expects($this->never())
            ->method('failed');

        $expected = Result::ok(
            new Payload(null, true),
            $this->createMock(QueryParameters::class),
        );

        $actual = $this->middleware->handle($query, function ($passed) use ($query, $expected): Result {
            $this->assertSame($query, $passed);
            return $expected;
        });

        $this->assertSame($expected, $actual);
    }

    /**
     * @return void
     */
    public function testItPassesAuthorizationWithoutRequest(): void
    {
        $query = FetchOneQuery::make(null, $this->type)
            ->withModel($model = new \stdClass());

        $this->authorizer
            ->expects($this->once())
            ->method('show')
            ->with(null, $this->identicalTo($model))
            ->willReturn(true);

        $this->authorizer
            ->expects($this->never())
            ->method('failed');

        $expected = Result::ok(
            new Payload(null, true),
            $this->createMock(QueryParameters::class),
        );

        $actual = $this->middleware->handle($query, function ($passed) use ($query, $expected): Result {
            $this->assertSame($query, $passed);
            return $expected;
        });

        $this->assertSame($expected, $actual);
    }

    /**
     * @return void
     */
    public function testItFailsAuthorizationWithException(): void
    {
        $request = $this->createMock(Request::class);

        $query = FetchOneQuery::make($request, $this->type)
            ->withModel($model = new \stdClass());

        $this->authorizer
            ->expects($this->once())
            ->method('show')
            ->with($this->identicalTo($request), $this->identicalTo($model))
            ->willReturn(false);

        $this->authorizer
            ->expects($this->once())
            ->method('failed')
            ->willReturn($expected = new \LogicException('Failed!'));

        try {
            $this->middleware->handle(
                $query,
                fn() => $this->fail('Expecting next middleware to not be called.'),
            );
            $this->fail('Middleware did not throw an exception.');
        } catch (\LogicException $actual) {
            $this->assertSame($expected, $actual);
        }
    }

    /**
     * @return void
     */
    public function testItFailsAuthorizationWithErrorList(): void
    {
        $request = $this->createMock(Request::class);

        $query = FetchOneQuery::make($request, $this->type)
            ->withModel($model = new \stdClass());

        $this->authorizer
            ->expects($this->once())
            ->method('show')
            ->with($this->identicalTo($request), $this->identicalTo($model))
            ->willReturn(false);

        $this->authorizer
            ->expects($this->once())
            ->method('failed')
            ->willReturn($expected = new ErrorList());

        $result = $this->middleware->handle(
            $query,
            fn() => $this->fail('Expecting next middleware not to be called.'),
        );

        $this->assertTrue($result->didFail());
        $this->assertSame($expected, $result->errors());
    }

    /**
     * @return void
     */
    public function testItFailsAuthorizationWithError(): void
    {
        $request = $this->createMock(Request::class);

        $query = FetchOneQuery::make($request, $this->type)
            ->withModel($model = new \stdClass());

        $this->authorizer
            ->expects($this->once())
            ->method('show')
            ->with($this->identicalTo($request), $this->identicalTo($model))
            ->willReturn(false);

        $this->authorizer
            ->expects($this->once())
            ->method('failed')
            ->willReturn($expected = new Error());

        $result = $this->middleware->handle(
            $query,
            fn() => $this->fail('Expecting next middleware not to be called.'),
        );

        $this->assertTrue($result->didFail());
        $this->assertSame([$expected], $result->errors()->all());
    }

    /**
     * @return void
     */
    public function testItSkipsAuthorization(): void
    {
        $request = $this->createMock(Request::class);

        $query = FetchOneQuery::make($request, $this->type)
            ->withModel(new \stdClass())
            ->skipAuthorization();

        $this->authorizer
            ->expects($this->never())
            ->method($this->anything());

        $expected = Result::ok(
            new Payload(null, true),
            $this->createMock(QueryParameters::class),
        );

        $actual = $this->middleware->handle($query, function ($passed) use ($query, $expected): Result {
            $this->assertSame($query, $passed);
            return $expected;
        });

        $this->assertSame($expected, $actual);
    }
}
