<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Bus\Commands\Destroy\Middleware;

use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Http\Hooks\DestroyImplementation;
use LaravelJsonApi\Core\Bus\Commands\Destroy\DestroyCommand;
use LaravelJsonApi\Core\Bus\Commands\Destroy\Middleware\TriggerDestroyHooks;
use LaravelJsonApi\Core\Bus\Commands\Result;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Delete;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result as Payload;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Ref;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\TestCase;
use stdClass;

class TriggerDestroyHooksTest extends TestCase
{
    /**
     * @var TriggerDestroyHooks
     */
    private TriggerDestroyHooks $middleware;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new TriggerDestroyHooks();
    }

    /**
     * @return void
     */
    public function testItHasNoHooks(): void
    {
        $command = DestroyCommand::make(
            $this->createMock(Request::class),
            new Delete(new Ref(new ResourceType('posts'), new ResourceId('123'))),
        )->withModel(new stdClass());

        $expected = Result::ok();

        $actual = $this->middleware->handle(
            $command,
            function (DestroyCommand $cmd) use ($command, $expected): Result {
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
        $hooks = $this->createMock(DestroyImplementation::class);
        $model = new stdClass();
        $sequence = [];

        $operation = new Delete(
            new Ref(new ResourceType('posts'), new ResourceId('123')),
        );

        $command = DestroyCommand::make($request, $operation)
            ->withModel($model)
            ->withHooks($hooks);

        $hooks
            ->expects($this->once())
            ->method('deleting')
            ->willReturnCallback(function ($m, $req) use (&$sequence, $model, $request): void {
                $sequence[] = 'deleting';
                $this->assertSame($model, $m);
                $this->assertSame($request, $req);
            });

        $hooks
            ->expects($this->once())
            ->method('deleted')
            ->willReturnCallback(function ($m, $req) use (&$sequence, $model, $request): void {
                $sequence[] = 'deleted';
                $this->assertSame($model, $m);
                $this->assertSame($request, $req);
            });

        $expected = Result::ok(new Payload($model, true));

        $actual = $this->middleware->handle(
            $command,
            function (DestroyCommand $cmd) use ($command, $expected, &$sequence): Result {
                $this->assertSame($command, $cmd);
                $this->assertSame(['deleting'], $sequence);
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
        $this->assertSame(['deleting', 'deleted'], $sequence);
    }

    /**
     * @return void
     */
    public function testItDoesNotTriggerAfterHooksIfItFails(): void
    {
        $request = $this->createMock(Request::class);
        $hooks = $this->createMock(DestroyImplementation::class);
        $model = new stdClass();
        $sequence = [];

        $operation = new Delete(
            new Ref(new ResourceType('posts'), new ResourceId('123')),
        );

        $command = DestroyCommand::make($request, $operation)
            ->withModel($model)
            ->withHooks($hooks);

        $hooks
            ->expects($this->once())
            ->method('deleting')
            ->willReturnCallback(function ($m, $req) use (&$sequence, $model, $request): void {
                $sequence[] = 'deleting';
                $this->assertSame($model, $m);
                $this->assertSame($request, $req);
            });

        $hooks
            ->expects($this->never())
            ->method('deleted');

        $expected = Result::failed();

        $actual = $this->middleware->handle(
            $command,
            function (DestroyCommand $cmd) use ($command, $expected, &$sequence): Result {
                $this->assertSame($command, $cmd);
                $this->assertSame(['deleting'], $sequence);
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
        $this->assertSame(['deleting'], $sequence);
    }
}
