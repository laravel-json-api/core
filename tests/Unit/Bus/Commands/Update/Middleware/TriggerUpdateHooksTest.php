<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Bus\Commands\Update\Middleware;

use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Http\Hooks\UpdateImplementation;
use LaravelJsonApi\Contracts\Query\QueryParameters;
use LaravelJsonApi\Core\Bus\Commands\Result;
use LaravelJsonApi\Core\Bus\Commands\Update\Middleware\TriggerUpdateHooks;
use LaravelJsonApi\Core\Bus\Commands\Update\UpdateCommand;
use LaravelJsonApi\Core\Document\Input\Values\ResourceObject;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Update;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result as Payload;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\TestCase;
use stdClass;

class TriggerUpdateHooksTest extends TestCase
{
    /**
     * @var TriggerUpdateHooks
     */
    private TriggerUpdateHooks $middleware;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new TriggerUpdateHooks();
    }

    /**
     * @return void
     */
    public function testItHasNoHooks(): void
    {
        $command = UpdateCommand::make(
            $this->createMock(Request::class),
            new Update(null, new ResourceObject(new ResourceType('posts'), new ResourceId('123'))),
        )->withModel(new stdClass());

        $expected = Result::ok();

        $actual = $this->middleware->handle(
            $command,
            function (UpdateCommand $cmd) use ($command, $expected): Result {
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
        $hooks = $this->createMock(UpdateImplementation::class);
        $query = $this->createMock(QueryParameters::class);
        $model = new stdClass();
        $sequence = [];

        $operation = new Update(
            null,
            new ResourceObject(new ResourceType('posts'), new ResourceId('123')),
        );

        $command = UpdateCommand::make($request, $operation)
            ->withModel($model)
            ->withHooks($hooks)
            ->withQuery($query);

        $hooks
            ->expects($this->once())
            ->method('saving')
            ->willReturnCallback(function ($m, $req, $q) use (&$sequence, $model, $request, $query): void {
                $sequence[] = 'saving';
                $this->assertSame($model, $m);
                $this->assertSame($request, $req);
                $this->assertSame($query, $q);
            });

        $hooks
            ->expects($this->once())
            ->method('updating')
            ->willReturnCallback(function ($m, $req, $q) use (&$sequence, $model, $request, $query): void {
                $sequence[] = 'updating';
                $this->assertSame($model, $m);
                $this->assertSame($request, $req);
                $this->assertSame($query, $q);
            });

        $hooks
            ->expects($this->once())
            ->method('updated')
            ->willReturnCallback(function ($m, $req, $q) use (&$sequence, $model, $request, $query): void {
                $sequence[] = 'updated';
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
            function (UpdateCommand $cmd) use ($command, $expected, &$sequence): Result {
                $this->assertSame($command, $cmd);
                $this->assertSame(['saving', 'updating'], $sequence);
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
        $this->assertSame(['saving', 'updating', 'updated', 'saved'], $sequence);
    }

    /**
     * @return void
     */
    public function testItDoesNotTriggerAfterHooksIfItFails(): void
    {
        $request = $this->createMock(Request::class);
        $hooks = $this->createMock(UpdateImplementation::class);
        $query = $this->createMock(QueryParameters::class);
        $model = new stdClass();
        $sequence = [];

        $operation = new Update(
            null,
            new ResourceObject(new ResourceType('posts'), new ResourceId('123')),
        );

        $command = UpdateCommand::make($request, $operation)
            ->withModel($model)
            ->withHooks($hooks)
            ->withQuery($query);

        $hooks
            ->expects($this->once())
            ->method('saving')
            ->willReturnCallback(function ($m, $req, $q) use (&$sequence, $model, $request, $query): void {
                $sequence[] = 'saving';
                $this->assertSame($model, $m);
                $this->assertSame($request, $req);
                $this->assertSame($query, $q);
            });

        $hooks
            ->expects($this->once())
            ->method('updating')
            ->willReturnCallback(function ($m, $req, $q) use (&$sequence, $model, $request, $query): void {
                $sequence[] = 'updating';
                $this->assertSame($model, $m);
                $this->assertSame($request, $req);
                $this->assertSame($query, $q);
            });

        $hooks
            ->expects($this->never())
            ->method('updated');

        $hooks
            ->expects($this->never())
            ->method('saved');

        $expected = Result::failed();

        $actual = $this->middleware->handle(
            $command,
            function (UpdateCommand $cmd) use ($command, $expected, &$sequence): Result {
                $this->assertSame($command, $cmd);
                $this->assertSame(['saving', 'updating'], $sequence);
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
        $this->assertSame(['saving', 'updating'], $sequence);
    }
}
