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

namespace LaravelJsonApi\Core\Tests\Unit\Bus\Commands\Destroy\Middleware;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Auth\ResourceAuthorizer;
use LaravelJsonApi\Core\Auth\ResourceAuthorizerFactory;
use LaravelJsonApi\Core\Bus\Commands\Destroy\DestroyCommand;
use LaravelJsonApi\Core\Bus\Commands\Destroy\Middleware\AuthorizeDestroyCommand;
use LaravelJsonApi\Core\Bus\Commands\Result;
use LaravelJsonApi\Core\Document\ErrorList;
use LaravelJsonApi\Core\Document\Input\Values\ResourceId;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Delete;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Ref;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;

class AuthorizeDestroyCommandTest extends TestCase
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
     * @var AuthorizeDestroyCommand
     */
    private AuthorizeDestroyCommand $middleware;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->type = new ResourceType('posts');

        $this->middleware = new AuthorizeDestroyCommand(
            $this->authorizerFactory = $this->createMock(ResourceAuthorizerFactory::class),
        );
    }

    /**
     * @return void
     */
    public function testItPassesAuthorizationWithRequest(): void
    {
        $command = DestroyCommand::make(
            $request = $this->createMock(Request::class),
            new Delete(new Ref($this->type, new ResourceId('123'))),
        )->withModel($model = new stdClass());

        $this->willAuthorize($request, $model, null);

        $expected = Result::ok();

        $actual = $this->middleware->handle($command, function ($cmd) use ($command, $expected): Result {
            $this->assertSame($command, $cmd);
            return $expected;
        });

        $this->assertSame($expected, $actual);
    }

    /**
     * @return void
     */
    public function testItPassesAuthorizationWithoutRequest(): void
    {
        $command = DestroyCommand::make(
            null,
            new Delete(new Ref($this->type, new ResourceId('123'))),
        )->withModel($model = new stdClass());

        $this->willAuthorize(null, $model, null);

        $expected = Result::ok();

        $actual = $this->middleware->handle($command, function ($cmd) use ($command, $expected): Result {
            $this->assertSame($command, $cmd);
            return $expected;
        });

        $this->assertSame($expected, $actual);
    }

    /**
     * @return void
     */
    public function testItFailsAuthorizationWithException(): void
    {
        $command = DestroyCommand::make(
            $request = $this->createMock(Request::class),
            new Delete(new Ref($this->type, new ResourceId('123'))),
        )->withModel($model = new stdClass());

        $this->willAuthorizeAndThrow(
            $request,
            $model,
            $expected = new AuthorizationException('Boom!'),
        );

        try {
            $this->middleware->handle(
                $command,
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
    public function testItFailsAuthorizationWithErrorList(): void
    {
        $command = DestroyCommand::make(
            $request = $this->createMock(Request::class),
            new Delete(new Ref($this->type, new ResourceId('123'))),
        )->withModel($model = new stdClass());

        $this->willAuthorize($request, $model, $expected = new ErrorList());

        $result = $this->middleware->handle(
            $command,
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
        $command = DestroyCommand::make(
            $this->createMock(Request::class),
            new Delete(new Ref($this->type, new ResourceId('123'))),
        )->withModel(new stdClass())->skipAuthorization();

        $this->authorizerFactory
            ->expects($this->never())
            ->method($this->anything());

        $expected = Result::ok();

        $actual = $this->middleware->handle($command, function ($cmd) use ($command, $expected): Result {
            $this->assertSame($command, $cmd);
            return $expected;
        });

        $this->assertSame($expected, $actual);
    }

    /**
     * @param Request|null $request
     * @param stdClass $model
     * @param ErrorList|null $expected
     * @return void
     */
    private function willAuthorize(?Request $request, stdClass $model, ?ErrorList $expected): void
    {
        $this->authorizerFactory
            ->expects($this->once())
            ->method('make')
            ->with($this->identicalTo($this->type))
            ->willReturn($authorizer = $this->createMock(ResourceAuthorizer::class));

        $authorizer
            ->expects($this->once())
            ->method('destroy')
            ->with($this->identicalTo($request), $this->identicalTo($model))
            ->willReturn($expected);
    }

    /**
     * @param Request|null $request
     * @param stdClass $model
     * @param AuthorizationException $expected
     * @return void
     */
    private function willAuthorizeAndThrow(?Request $request, stdClass $model, AuthorizationException $expected): void
    {
        $this->authorizerFactory
            ->expects($this->once())
            ->method('make')
            ->with($this->identicalTo($this->type))
            ->willReturn($authorizer = $this->createMock(ResourceAuthorizer::class));

        $authorizer
            ->expects($this->once())
            ->method('destroy')
            ->with($this->identicalTo($request), $this->identicalTo($model))
            ->willThrowException($expected);
    }
}
