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

namespace LaravelJsonApi\Core\Tests\Unit\Bus\Commands\Store\Middleware;

use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer;
use LaravelJsonApi\Contracts\Auth\Container as AuthorizerContainer;
use LaravelJsonApi\Contracts\Schema\Container as SchemaContainer;
use LaravelJsonApi\Core\Bus\Commands\Result;
use LaravelJsonApi\Core\Bus\Commands\Store\Middleware\AuthorizeStoreCommand;
use LaravelJsonApi\Core\Bus\Commands\Store\StoreCommand;
use LaravelJsonApi\Core\Document\Error;
use LaravelJsonApi\Core\Document\ErrorList;
use LaravelJsonApi\Core\Document\Input\Values\ResourceObject;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Store;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Href;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AuthorizeStoreCommandTest extends TestCase
{
    /**
     * @var ResourceType
     */
    private ResourceType $type;

    /**
     * @var string
     */
    private string $modelClass;

    /**
     * @var Authorizer&MockObject
     */
    private Authorizer $authorizer;

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

        $authorizers = $this->createMock(AuthorizerContainer::class);
        $authorizers
            ->method('authorizerFor')
            ->with($this->identicalTo($this->type))
            ->willReturn($this->authorizer = $this->createMock(Authorizer::class));

        $schemas = $this->createMock(SchemaContainer::class);
        $schemas
            ->method('modelClassFor')
            ->with($this->identicalTo($this->type))
            ->willReturn($this->modelClass = 'App\Models\Post');

        $this->middleware = new AuthorizeStoreCommand(
            $authorizers,
            $schemas,
        );
    }

    /**
     * @return void
     */
    public function testItPassesAuthorizationWithRequest(): void
    {
        $command = new StoreCommand(
            $request = $this->createMock(Request::class),
            new Store(new Href('/posts'), new ResourceObject($this->type)),
        );

        $this->authorizer
            ->expects($this->once())
            ->method('store')
            ->with($this->identicalTo($request), $this->modelClass)
            ->willReturn(true);

        $this->authorizer
            ->expects($this->never())
            ->method('failed');

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
            new Store(new Href('/posts'), new ResourceObject($this->type)),
        );

        $this->authorizer
            ->expects($this->once())
            ->method('store')
            ->with(null, $this->modelClass)
            ->willReturn(true);

        $this->authorizer
            ->expects($this->never())
            ->method('failed');

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
            new Store(new Href('/posts'), new ResourceObject($this->type)),
        );

        $this->authorizer
            ->expects($this->once())
            ->method('store')
            ->with($this->identicalTo($request), $this->modelClass)
            ->willReturn(false);

        $this->authorizer
            ->expects($this->once())
            ->method('failed')
            ->willReturn($expected = new \LogicException('Failed!'));

        try {
            $this->middleware->handle(
                $command,
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
        $command = new StoreCommand(
            $request = $this->createMock(Request::class),
            new Store(new Href('/posts'), new ResourceObject($this->type)),
        );

        $this->authorizer
            ->expects($this->once())
            ->method('store')
            ->with($this->identicalTo($request), $this->modelClass)
            ->willReturn(false);

        $this->authorizer
            ->expects($this->once())
            ->method('failed')
            ->willReturn($expected = new ErrorList());

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
    public function testItFailsAuthorizationWithError(): void
    {
        $command = new StoreCommand(
            $request = $this->createMock(Request::class),
            new Store(new Href('/posts'), new ResourceObject($this->type)),
        );

        $this->authorizer
            ->expects($this->once())
            ->method('store')
            ->with($this->identicalTo($request), $this->modelClass)
            ->willReturn(false);

        $this->authorizer
            ->expects($this->once())
            ->method('failed')
            ->willReturn($expected = new Error());

        $result = $this->middleware->handle(
            $command,
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
        $command = StoreCommand::make(
            $this->createMock(Request::class),
            new Store(new Href('/posts'), new ResourceObject($this->type)),
        )->skipAuthorization();

        $this->authorizer
            ->expects($this->never())
            ->method($this->anything());

        $expected = Result::ok();

        $actual = $this->middleware->handle($command, function ($cmd) use ($command, $expected): Result {
            $this->assertSame($command, $cmd);
            return $expected;
        });

        $this->assertSame($expected, $actual);
    }
}
