<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Bus\Commands\Store\Middleware;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\ResourceAuthorizer;
use LaravelJsonApi\Contracts\Auth\ResourceAuthorizerFactory;
use LaravelJsonApi\Core\Bus\Commands\Result;
use LaravelJsonApi\Core\Bus\Commands\Store\Middleware\AuthorizeStoreCommand;
use LaravelJsonApi\Core\Bus\Commands\Store\StoreCommand;
use LaravelJsonApi\Core\Document\ErrorList;
use LaravelJsonApi\Core\Document\Input\Values\ResourceObject;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Create;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AuthorizeStoreCommandTest extends TestCase
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
     * @var AuthorizeStoreCommand
     */
    private AuthorizeStoreCommand $middleware;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->type = new ResourceType('posts');

        $this->middleware = new AuthorizeStoreCommand(
            $this->authorizerFactory = $this->createMock(ResourceAuthorizerFactory::class),
        );
    }

    /**
     * @return void
     */
    public function testItPassesAuthorizationWithRequest(): void
    {
        $command = new StoreCommand(
            $request = $this->createMock(Request::class),
            new Create(null, new ResourceObject($this->type)),
        );

        $this->willAuthorize($request, null);

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
        $command = new StoreCommand(
            null,
            new Create(null, new ResourceObject($this->type)),
        );

        $this->willAuthorize(null, null);

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
        $command = new StoreCommand(
            $request = $this->createMock(Request::class),
            new Create(null, new ResourceObject($this->type)),
        );

        $this->willAuthorizeAndThrow(
            $request,
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
        $command = new StoreCommand(
            $request = $this->createMock(Request::class),
            new Create(null, new ResourceObject($this->type)),
        );

        $this->willAuthorize($request, $expected = new ErrorList());

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
        $command = StoreCommand::make(
            $this->createMock(Request::class),
            new Create(null, new ResourceObject($this->type)),
        )->skipAuthorization();

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
     * @param ErrorList|null $expected
     * @return void
     */
    private function willAuthorize(?Request $request, ?ErrorList $expected): void
    {
        $this->authorizerFactory
            ->expects($this->once())
            ->method('make')
            ->with($this->identicalTo($this->type))
            ->willReturn($authorizer = $this->createMock(ResourceAuthorizer::class));

        $authorizer
            ->expects($this->once())
            ->method('store')
            ->with($this->identicalTo($request))
            ->willReturn($expected);
    }

    /**
     * @param Request|null $request
     * @return void
     */
    private function willAuthorizeAndThrow(?Request $request, AuthorizationException $expected): void
    {
        $this->authorizerFactory
            ->expects($this->once())
            ->method('make')
            ->with($this->identicalTo($this->type))
            ->willReturn($authorizer = $this->createMock(ResourceAuthorizer::class));

        $authorizer
            ->expects($this->once())
            ->method('store')
            ->with($this->identicalTo($request))
            ->willThrowException($expected);
    }
}
