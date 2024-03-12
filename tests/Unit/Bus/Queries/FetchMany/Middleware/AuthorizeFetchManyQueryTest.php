<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Bus\Queries\FetchMany\Middleware;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Query\QueryParameters;
use LaravelJsonApi\Core\Auth\ResourceAuthorizer;
use LaravelJsonApi\Core\Auth\ResourceAuthorizerFactory;
use LaravelJsonApi\Core\Bus\Queries\FetchMany\FetchManyQuery;
use LaravelJsonApi\Core\Bus\Queries\FetchMany\Middleware\AuthorizeFetchManyQuery;
use LaravelJsonApi\Core\Bus\Queries\Result;
use LaravelJsonApi\Core\Document\ErrorList;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result as Payload;
use LaravelJsonApi\Core\Query\Input\QueryMany;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AuthorizeFetchManyQueryTest extends TestCase
{
    /**
     * @var ResourceType
     */
    private ResourceType $type;

    /**
     * @var ResourceAuthorizerFactory&MockObject
     */
    private ResourceAuthorizerFactory&MockObject $authorizerFactory;

    /**
     * @var AuthorizeFetchManyQuery
     */
    private AuthorizeFetchManyQuery $middleware;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->type = new ResourceType('posts');

        $this->middleware = new AuthorizeFetchManyQuery(
            $this->authorizerFactory = $this->createMock(ResourceAuthorizerFactory::class),
        );
    }

    /**
     * @return void
     */
    public function testItPassesAuthorizationWithRequest(): void
    {
        $request = $this->createMock(Request::class);

        $query = FetchManyQuery::make($request, new QueryMany($this->type));

        $this->willAuthorize($request);

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
        $query = FetchManyQuery::make(null, new QueryMany($this->type));

        $this->willAuthorize(null);

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

        $query = FetchManyQuery::make($request, new QueryMany($this->type));

        $this->willAuthorizeAndThrow(
            $request,
            $expected = new AuthorizationException('Boom!'),
        );

        try {
            $this->middleware->handle(
                $query,
                fn() => $this->fail('Expecting next middleware to not be called.'),
            );
            $this->fail('Middleware did not throw an exception.');
        } catch (AuthorizationException $actual) {
            $this->assertSame($expected, $actual);
        }
    }

    /**
     * @return void
     */
    public function testItFailsAuthorizationWithErrors(): void
    {
        $request = $this->createMock(Request::class);

        $query = FetchManyQuery::make($request, new QueryMany($this->type));

        $this->willAuthorize($request, $expected = new ErrorList());

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
    public function testItSkipsAuthorization(): void
    {
        $request = $this->createMock(Request::class);

        $query = FetchManyQuery::make($request, new QueryMany($this->type))
            ->skipAuthorization();

        $this->authorizerFactory
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

    /**
     * @param Request|null $request
     * @param ErrorList|null $expected
     * @return void
     */
    private function willAuthorize(?Request $request, ?ErrorList $expected = null): void
    {
        $this->authorizerFactory
            ->expects($this->once())
            ->method('make')
            ->with($this->identicalTo($this->type))
            ->willReturn($authorizer = $this->createMock(ResourceAuthorizer::class));

        $authorizer
            ->expects($this->once())
            ->method('index')
            ->with($this->identicalTo($request))
            ->willReturn($expected);
    }

    /**
     * @param Request|null $request
     * @param AuthorizationException $expected
     * @return void
     */
    private function willAuthorizeAndThrow(
        ?Request $request,
        AuthorizationException $expected,
    ): void
    {
        $this->authorizerFactory
            ->expects($this->once())
            ->method('make')
            ->with($this->identicalTo($this->type))
            ->willReturn($authorizer = $this->createMock(ResourceAuthorizer::class));

        $authorizer
            ->expects($this->once())
            ->method('index')
            ->with($this->identicalTo($request))
            ->willThrowException($expected);
    }
}
