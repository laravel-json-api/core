<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Bus\Commands\Destroy;

use Illuminate\Contracts\Pipeline\Pipeline;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Store\Store as StoreContract;
use LaravelJsonApi\Core\Bus\Commands\Destroy\DestroyCommand;
use LaravelJsonApi\Core\Bus\Commands\Destroy\DestroyCommandHandler;
use LaravelJsonApi\Core\Bus\Commands\Destroy\Middleware\AuthorizeDestroyCommand;
use LaravelJsonApi\Core\Bus\Commands\Destroy\Middleware\TriggerDestroyHooks;
use LaravelJsonApi\Core\Bus\Commands\Destroy\Middleware\ValidateDestroyCommand;
use LaravelJsonApi\Core\Bus\Commands\Middleware\SetModelIfMissing;
use LaravelJsonApi\Core\Bus\Commands\Result;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Delete;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Ref;
use LaravelJsonApi\Core\Support\PipelineFactory;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;

class DestroyCommandHandlerTest extends TestCase
{
    /**
     * @var PipelineFactory&MockObject
     */
    private PipelineFactory&MockObject $pipelineFactory;

    /**
     * @var MockObject&StoreContract
     */
    private StoreContract&MockObject $store;

    /**
     * @var DestroyCommandHandler
     */
    private DestroyCommandHandler $handler;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = new DestroyCommandHandler(
            $this->pipelineFactory = $this->createMock(PipelineFactory::class),
            $this->store = $this->createMock(StoreContract::class),
        );
    }

    /**
     * @return void
     */
    public function testItDeletesUsingModel(): void
    {
        $original = new DestroyCommand(
            $request = $this->createMock(Request::class),
            $operation = new Delete(new Ref(new ResourceType('posts'), new ResourceId('123'))),
        );

        $passed = DestroyCommand::make($request, $operation)
            ->withModel($model = new stdClass());

        $sequence = [];

        $this->pipelineFactory
            ->expects($this->once())
            ->method('pipe')
            ->with($this->identicalTo($original))
            ->willReturn($pipeline = $this->createMock(Pipeline::class));

        $pipeline
            ->expects($this->once())
            ->method('through')
            ->willReturnCallback(function (array $actual) use (&$sequence, $pipeline): Pipeline {
                $sequence[] = 'through';
                $this->assertSame([
                    SetModelIfMissing::class,
                    AuthorizeDestroyCommand::class,
                    ValidateDestroyCommand::class,
                    TriggerDestroyHooks::class,
                ], $actual);
                return $pipeline;
            });

        $pipeline
            ->expects($this->once())
            ->method('via')
            ->with('handle')
            ->willReturnCallback(function () use (&$sequence, $pipeline): Pipeline {
                $sequence[] = 'via';
                return $pipeline;
            });

        $pipeline
            ->expects($this->once())
            ->method('then')
            ->willReturnCallback(function (\Closure $fn) use ($passed, &$sequence): Result {
                $this->assertSame(['through', 'via'], $sequence);
                return $fn($passed);
            });

        $this->store
            ->expects($this->once())
            ->method('delete')
            ->with($this->identicalTo($passed->type()), $this->identicalTo($model));

        $payload = $this->handler
            ->execute($original)
            ->payload();

        $this->assertFalse($payload->hasData);
        $this->assertNull($payload->data);
        $this->assertEmpty($payload->meta);
    }

    /**
     * @return void
     */
    public function testItDeletesUsingResourceId(): void
    {
        $original = new DestroyCommand(
            $request = $this->createMock(Request::class),
            $operation = new Delete(new Ref(new ResourceType('posts'), $id = new ResourceId('123'))),
        );

        $passed = DestroyCommand::make($request, $operation);

        $sequence = [];

        $this->pipelineFactory
            ->expects($this->once())
            ->method('pipe')
            ->with($this->identicalTo($original))
            ->willReturn($pipeline = $this->createMock(Pipeline::class));

        $pipeline
            ->expects($this->once())
            ->method('through')
            ->willReturnCallback(function (array $actual) use (&$sequence, $pipeline): Pipeline {
                $sequence[] = 'through';
                $this->assertSame([
                    SetModelIfMissing::class,
                    AuthorizeDestroyCommand::class,
                    ValidateDestroyCommand::class,
                    TriggerDestroyHooks::class,
                ], $actual);
                return $pipeline;
            });

        $pipeline
            ->expects($this->once())
            ->method('via')
            ->with('handle')
            ->willReturnCallback(function () use (&$sequence, $pipeline): Pipeline {
                $sequence[] = 'via';
                return $pipeline;
            });

        $pipeline
            ->expects($this->once())
            ->method('then')
            ->willReturnCallback(function (\Closure $fn) use ($passed, &$sequence): Result {
                $this->assertSame(['through', 'via'], $sequence);
                return $fn($passed);
            });

        $this->store
            ->expects($this->once())
            ->method('delete')
            ->with($this->identicalTo($passed->type()), $this->identicalTo($id));

        $payload = $this->handler
            ->execute($original)
            ->payload();

        $this->assertFalse($payload->hasData);
        $this->assertNull($payload->data);
        $this->assertEmpty($payload->meta);
    }
}
