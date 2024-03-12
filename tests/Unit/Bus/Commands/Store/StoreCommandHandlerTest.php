<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Bus\Commands\Store;

use Illuminate\Contracts\Pipeline\Pipeline;
use Illuminate\Http\Request;
use Illuminate\Support\ValidatedInput;
use LaravelJsonApi\Contracts\Store\ResourceBuilder;
use LaravelJsonApi\Contracts\Store\Store as StoreContract;
use LaravelJsonApi\Core\Bus\Commands\Result;
use LaravelJsonApi\Core\Bus\Commands\Store\Middleware\AuthorizeStoreCommand;
use LaravelJsonApi\Core\Bus\Commands\Store\Middleware\TriggerStoreHooks;
use LaravelJsonApi\Core\Bus\Commands\Store\Middleware\ValidateStoreCommand;
use LaravelJsonApi\Core\Bus\Commands\Store\StoreCommand;
use LaravelJsonApi\Core\Bus\Commands\Store\StoreCommandHandler;
use LaravelJsonApi\Core\Document\Input\Values\ResourceObject;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Create;
use LaravelJsonApi\Core\Support\PipelineFactory;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StoreCommandHandlerTest extends TestCase
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
     * @var StoreCommandHandler
     */
    private StoreCommandHandler $handler;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = new StoreCommandHandler(
            $this->pipelineFactory = $this->createMock(PipelineFactory::class),
            $this->store = $this->createMock(StoreContract::class),
        );
    }

    /**
     * @return void
     */
    public function test(): void
    {
        $original = new StoreCommand(
            $request = $this->createMock(Request::class),
            $operation = new Create(null, new ResourceObject(new ResourceType('posts'))),
        );

        $passed = StoreCommand::make($request, $operation)
            ->withValidated($validated = ['foo' => 'bar']);

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
                    AuthorizeStoreCommand::class,
                    ValidateStoreCommand::class,
                    TriggerStoreHooks::class,
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
            ->method('create')
            ->with($this->identicalTo($passed->type()))
            ->willReturn($builder = $this->createMock(ResourceBuilder::class));

        $builder
            ->expects($this->once())
            ->method('withRequest')
            ->with($this->identicalTo($request))
            ->willReturnSelf();

        $builder
            ->expects($this->once())
            ->method('store')
            ->with($this->equalTo(new ValidatedInput($validated)))
            ->willReturn($model = new \stdClass());

        $payload = $this->handler
            ->execute($original)
            ->payload();

        $this->assertTrue($payload->hasData);
        $this->assertSame($model, $payload->data);
        $this->assertEmpty($payload->meta);
    }
}
