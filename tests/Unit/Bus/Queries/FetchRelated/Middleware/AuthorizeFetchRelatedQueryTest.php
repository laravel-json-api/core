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

namespace LaravelJsonApi\Core\Tests\Unit\Bus\Queries\FetchRelated\Middleware;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Query\QueryParameters;
use LaravelJsonApi\Core\Auth\ResourceAuthorizer;
use LaravelJsonApi\Core\Auth\ResourceAuthorizerFactory;
use LaravelJsonApi\Core\Bus\Queries\FetchRelated\FetchRelatedQuery;
use LaravelJsonApi\Core\Bus\Queries\FetchRelated\Middleware\AuthorizeFetchRelatedQuery;
use LaravelJsonApi\Core\Bus\Queries\Result;
use LaravelJsonApi\Core\Document\ErrorList;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result as Payload;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AuthorizeFetchRelatedQueryTest extends TestCase
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
     * @var AuthorizeFetchRelatedQuery
     */
    private AuthorizeFetchRelatedQuery $middleware;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->type = new ResourceType('posts');

        $this->middleware = new AuthorizeFetchRelatedQuery(
            $this->authorizerFactory = $this->createMock(ResourceAuthorizerFactory::class),
        );
    }

    /**
     * @return void
     */
    public function testItPassesAuthorizationWithRequest(): void
    {
        $request = $this->createMock(Request::class);

        $query = FetchRelatedQuery::make($request, $this->type)
            ->withFieldName('comments')
            ->withModel($model = new \stdClass());

        $this->willAuthorize($request, $model, 'comments');

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
        $query = FetchRelatedQuery::make(null, $this->type)
            ->withFieldName('tags')
            ->withModel($model = new \stdClass());

        $this->willAuthorize(null, $model, 'tags');

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

        $query = FetchRelatedQuery::make($request, $this->type)
            ->withFieldName('comments')
            ->withModel($model = new \stdClass());

        $this->willAuthorizeAndThrow(
            $request,
            $model,
            'comments',
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

        $query = FetchRelatedQuery::make($request, $this->type)
            ->withFieldName('tags')
            ->withModel($model = new \stdClass());

        $this->willAuthorize($request, $model, 'tags', $expected = new ErrorList());

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

        $query = FetchRelatedQuery::make($request, $this->type)
            ->withFieldName('videos')
            ->withModel(new \stdClass())
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
     * @param object $model
     * @param string $fieldName
     * @param ErrorList|null $expected
     * @return void
     */
    private function willAuthorize(
        ?Request $request,
        object $model,
        string $fieldName,
        ErrorList $expected = null
    ): void
    {
        $this->authorizerFactory
            ->expects($this->once())
            ->method('make')
            ->with($this->identicalTo($this->type))
            ->willReturn($authorizer = $this->createMock(ResourceAuthorizer::class));

        $authorizer
            ->expects($this->once())
            ->method('showRelated')
            ->with($this->identicalTo($request), $this->identicalTo($model), $this->identicalTo($fieldName))
            ->willReturn($expected);
    }

    /**
     * @param Request|null $request
     * @param object $model
     * @param string $fieldName
     * @param AuthorizationException $expected
     * @return void
     */
    private function willAuthorizeAndThrow(
        ?Request $request,
        object $model,
        string $fieldName,
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
            ->method('showRelated')
            ->with($this->identicalTo($request), $this->identicalTo($model), $this->identicalTo($fieldName))
            ->willThrowException($expected);
    }
}
