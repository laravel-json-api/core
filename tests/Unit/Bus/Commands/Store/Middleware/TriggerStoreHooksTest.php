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
use LaravelJsonApi\Contracts\Http\Controllers\Hooks\StoreImplementation;
use LaravelJsonApi\Contracts\Query\QueryParameters;
use LaravelJsonApi\Core\Bus\Commands\Result;
use LaravelJsonApi\Core\Bus\Commands\Store\Middleware\TriggerStoreHooks;
use LaravelJsonApi\Core\Bus\Commands\Store\StoreCommand;
use LaravelJsonApi\Core\Document\Input\Values\ResourceObject;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Store;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result as Payload;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Href;
use PHPUnit\Framework\TestCase;

class TriggerStoreHooksTest extends TestCase
{
    /**
     * @var TriggerStoreHooks
     */
    private TriggerStoreHooks $middleware;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new TriggerStoreHooks();
    }

    /**
     * @return void
     */
    public function testItHasNoHooks(): void
    {
        $command = new StoreCommand(
            $this->createMock(Request::class),
            new Store(new Href('/posts'), new ResourceObject(new ResourceType('posts'))),
        );

        $expected = Result::ok();

        $actual = $this->middleware->handle(
            $command,
            function (StoreCommand $cmd) use ($command, $expected): Result {
                $this->assertSame($command, $cmd);
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
    }

    /**
     * @return void
     */
    public function testItTriggersHooks(): void
    {
        $request = $this->createMock(Request::class);
        $hooks = $this->createMock(StoreImplementation::class);
        $query = $this->createMock(QueryParameters::class);
        $model = new \stdClass();
        $sequence = [];

        $operation = new Store(
            new Href('/posts'),
            new ResourceObject(new ResourceType('posts')),
        );

        $command = StoreCommand::make($request, $operation)
            ->withHooks($hooks)
            ->withQuery($query);

        $hooks
            ->expects($this->once())
            ->method('saving')
            ->willReturnCallback(function ($model, $req, $q) use (&$sequence, $request, $query): void {
                $sequence[] = 'saving';
                $this->assertNull($model);
                $this->assertSame($request, $req);
                $this->assertSame($query, $q);
            });

        $hooks
            ->expects($this->once())
            ->method('creating')
            ->willReturnCallback(function ($req, $q) use (&$sequence, $request, $query): void {
                $sequence[] = 'creating';
                $this->assertSame($request, $req);
                $this->assertSame($query, $q);
            });

        $hooks
            ->expects($this->once())
            ->method('created')
            ->willReturnCallback(function ($m, $req, $q) use (&$sequence, $model, $request, $query): void {
                $sequence[] = 'created';
                $this->assertSame($model, $m);
                $this->assertSame($request, $req);
                $this->assertSame($query, $q);
            });

        $hooks
            ->expects($this->once())
            ->method('saved')
            ->willReturnCallback(function ($m, $req, $q) use (&$sequence, $model, $request, $query): void {
                $sequence[] = 'saved';
                $this->assertSame($model, $m);
                $this->assertSame($request, $req);
                $this->assertSame($query, $q);
            });

        $expected = Result::ok(new Payload($model, true));

        $actual = $this->middleware->handle(
            $command,
            function (StoreCommand $cmd) use ($command, $expected, &$sequence): Result {
                $this->assertSame($command, $cmd);
                $this->assertSame(['saving', 'creating'], $sequence);
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
        $this->assertSame(['saving', 'creating', 'created', 'saved'], $sequence);
    }

    /**
     * @return void
     */
    public function testItDoesNotTriggerAfterHooksIfItFails(): void
    {
        $request = $this->createMock(Request::class);
        $hooks = $this->createMock(StoreImplementation::class);
        $query = $this->createMock(QueryParameters::class);
        $sequence = [];

        $operation = new Store(
            new Href('/posts'),
            new ResourceObject(new ResourceType('posts')),
        );

        $command = StoreCommand::make($request, $operation)
            ->withHooks($hooks)
            ->withQuery($query);

        $hooks
            ->expects($this->once())
            ->method('saving')
            ->willReturnCallback(function ($model, $req, $q) use (&$sequence, $request, $query): void {
                $sequence[] = 'saving';
                $this->assertNull($model);
                $this->assertSame($request, $req);
                $this->assertSame($query, $q);
            });

        $hooks
            ->expects($this->once())
            ->method('creating')
            ->willReturnCallback(function ($req, $q) use (&$sequence, $request, $query): void {
                $sequence[] = 'creating';
                $this->assertSame($request, $req);
                $this->assertSame($query, $q);
            });

        $hooks
            ->expects($this->never())
            ->method('created');

        $hooks
            ->expects($this->never())
            ->method('saved');

        $expected = Result::failed();

        $actual = $this->middleware->handle(
            $command,
            function (StoreCommand $cmd) use ($command, $expected, &$sequence): Result {
                $this->assertSame($command, $cmd);
                $this->assertSame(['saving', 'creating'], $sequence);
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
        $this->assertSame(['saving', 'creating'], $sequence);
    }
}
