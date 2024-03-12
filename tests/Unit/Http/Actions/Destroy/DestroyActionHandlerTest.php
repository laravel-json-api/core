<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Http\Actions\Destroy;

use Closure;
use Illuminate\Contracts\Pipeline\Pipeline;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Bus\Commands\Dispatcher as CommandDispatcher;
use LaravelJsonApi\Core\Bus\Commands\Destroy\DestroyCommand;
use LaravelJsonApi\Core\Bus\Commands\Result as CommandResult;
use LaravelJsonApi\Core\Document\ErrorList;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Delete;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result as Payload;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Ref;
use LaravelJsonApi\Core\Http\Actions\Destroy\DestroyActionHandler;
use LaravelJsonApi\Core\Http\Actions\Destroy\DestroyActionInput;
use LaravelJsonApi\Core\Http\Actions\Destroy\Middleware\ParseDeleteOperation;
use LaravelJsonApi\Core\Http\Actions\Middleware\ItAcceptsJsonApiResponses;
use LaravelJsonApi\Core\Http\Hooks\HooksImplementation;
use LaravelJsonApi\Core\Responses\MetaResponse;
use LaravelJsonApi\Core\Responses\NoContentResponse;
use LaravelJsonApi\Core\Support\PipelineFactory;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DestroyActionHandlerTest extends TestCase
{
    /**
     * @var PipelineFactory&MockObject
     */
    private PipelineFactory&MockObject $pipelineFactory;

    /**
     * @var MockObject&CommandDispatcher
     */
    private CommandDispatcher&MockObject $commandDispatcher;

    /**
     * @var DestroyActionHandler
     */
    private DestroyActionHandler $handler;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = new DestroyActionHandler(
            $this->pipelineFactory = $this->createMock(PipelineFactory::class),
            $this->commandDispatcher = $this->createMock(CommandDispatcher::class),
        );
    }

    /**
     * @return void
     */
    public function testItIsSuccessfulWithNoContent(): void
    {
        $request = $this->createMock(Request::class);
        $type = new ResourceType('comments2');
        $id = new ResourceId('123');

        $passed = (new DestroyActionInput($request, $type, $id))
            ->withModel($model = new \stdClass())
            ->withOperation($op = new Delete(new Ref($type, $id)))
            ->withHooks($hooks = new \stdClass());

        $original = $this->willSendThroughPipeline($passed);

        $this->commandDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(
                function (DestroyCommand $command) use ($request, $model, $op, $hooks): bool {
                    $this->assertSame($request, $command->request());
                    $this->assertSame($model, $command->model());
                    $this->assertSame($op, $command->operation());
                    $this->assertObjectEquals(new HooksImplementation($hooks), $command->hooks());
                    $this->assertTrue($command->mustAuthorize());
                    $this->assertTrue($command->mustValidate());
                    return true;
                },
            ))
            ->willReturn(CommandResult::ok(Payload::none()));

        $response = $this->handler->execute($original);

        $this->assertInstanceOf(NoContentResponse::class, $response);
    }

    /**
     * @return void
     */
    public function testItIsSuccessfulWithMeta(): void
    {
        $request = $this->createMock(Request::class);
        $type = new ResourceType('comments2');
        $id = new ResourceId('123');

        $passed = (new DestroyActionInput($request, $type, $id))
            ->withModel($model = new \stdClass())
            ->withOperation($op = new Delete(new Ref($type, $id)))
            ->withHooks($hooks = new \stdClass());

        $original = $this->willSendThroughPipeline($passed);

        $this->commandDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(
                function (DestroyCommand $command) use ($request, $model, $op, $hooks): bool {
                    $this->assertSame($request, $command->request());
                    $this->assertSame($model, $command->model());
                    $this->assertSame($op, $command->operation());
                    $this->assertObjectEquals(new HooksImplementation($hooks), $command->hooks());
                    $this->assertTrue($command->mustAuthorize());
                    $this->assertTrue($command->mustValidate());
                    return true;
                },
            ))
            ->willReturn(CommandResult::ok(Payload::none($meta = ['foo' => 'bar'])));

        $response = $this->handler->execute($original);

        $this->assertInstanceOf(MetaResponse::class, $response);
        $this->assertSame($meta, $response->meta()->all());
    }

    /**
     * @return void
     */
    public function testItHandlesFailedCommandResult(): void
    {
        $request = $this->createMock(Request::class);
        $type = new ResourceType('comments2');
        $id = new ResourceId('123');

        $passed = (new DestroyActionInput($request, $type, $id))
            ->withModel(new \stdClass())
            ->withOperation(new Delete(new Ref($type, $id)));

        $original = $this->willSendThroughPipeline($passed);

        $this->commandDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->willReturn(CommandResult::failed($expected = new ErrorList()));

        try {
            $this->handler->execute($original);
            $this->fail('No exception thrown.');
        } catch (JsonApiException $ex) {
            $this->assertSame($expected, $ex->getErrors());
        }
    }

    /**
     * @param DestroyActionInput $passed
     * @return DestroyActionInput
     */
    private function willSendThroughPipeline(DestroyActionInput $passed): DestroyActionInput
    {
        $original = new DestroyActionInput(
            $this->createMock(Request::class),
            new ResourceType('comments1'),
            new ResourceId('123'),
        );

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
                    ItAcceptsJsonApiResponses::class,
                    ParseDeleteOperation::class,
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
            ->willReturnCallback(function (Closure $fn) use ($passed, &$sequence): MetaResponse|NoContentResponse {
                $this->assertSame(['through', 'via'], $sequence);
                return $fn($passed);
            });

        return $original;
    }
}
