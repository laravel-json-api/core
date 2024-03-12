<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Bus\Commands\Update;

use Illuminate\Contracts\Pipeline\Pipeline;
use Illuminate\Http\Request;
use Illuminate\Support\ValidatedInput;
use LaravelJsonApi\Contracts\Store\ResourceBuilder;
use LaravelJsonApi\Contracts\Store\Store as StoreContract;
use LaravelJsonApi\Core\Bus\Commands\Middleware\SetModelIfMissing;
use LaravelJsonApi\Core\Bus\Commands\Result;
use LaravelJsonApi\Core\Bus\Commands\Update\Middleware\AuthorizeUpdateCommand;
use LaravelJsonApi\Core\Bus\Commands\Update\Middleware\TriggerUpdateHooks;
use LaravelJsonApi\Core\Bus\Commands\Update\Middleware\ValidateUpdateCommand;
use LaravelJsonApi\Core\Bus\Commands\Update\UpdateCommand;
use LaravelJsonApi\Core\Bus\Commands\Update\UpdateCommandHandler;
use LaravelJsonApi\Core\Document\Input\Values\ResourceObject;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Update;
use LaravelJsonApi\Core\Support\PipelineFactory;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;

class UpdateCommandHandlerTest extends TestCase
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
     * @var UpdateCommandHandler
     */
    private UpdateCommandHandler $handler;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = new UpdateCommandHandler(
            $this->pipelineFactory = $this->createMock(PipelineFactory::class),
            $this->store = $this->createMock(StoreContract::class),
        );
    }

    /**
     * @return void
     */
    public function test(): void
    {
        $original = new UpdateCommand(
            $request = $this->createMock(Request::class),
            $operation = new Update(null, new ResourceObject(new ResourceType('posts'), new ResourceId('123'))),
        );

        $passed = UpdateCommand::make($request, $operation)
            ->withModel($model = new stdClass())
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
                    SetModelIfMissing::class,
                    AuthorizeUpdateCommand::class,
                    ValidateUpdateCommand::class,
                    TriggerUpdateHooks::class,
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
            ->method('update')
            ->with($this->identicalTo($passed->type()), $this->identicalTo($model))
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
            ->willReturn($expected = new stdClass());

        $payload = $this->handler
            ->execute($original)
            ->payload();

        $this->assertTrue($payload->hasData);
        $this->assertSame($expected, $payload->data);
        $this->assertEmpty($payload->meta);
    }
}
