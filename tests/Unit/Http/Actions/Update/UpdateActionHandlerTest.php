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

namespace LaravelJsonApi\Core\Tests\Unit\Http\Actions\Update;

use Closure;
use Illuminate\Contracts\Pipeline\Pipeline;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Bus\Commands\Dispatcher as CommandDispatcher;
use LaravelJsonApi\Contracts\Bus\Queries\Dispatcher as QueryDispatcher;
use LaravelJsonApi\Contracts\Query\QueryParameters;
use LaravelJsonApi\Core\Bus\Commands\Result as CommandResult;
use LaravelJsonApi\Core\Bus\Commands\Update\UpdateCommand;
use LaravelJsonApi\Core\Bus\Queries\FetchOne\FetchOneQuery;
use LaravelJsonApi\Core\Bus\Queries\Result as QueryResult;
use LaravelJsonApi\Core\Document\ErrorList;
use LaravelJsonApi\Core\Document\Input\Values\ResourceObject;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Update;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result as Payload;
use LaravelJsonApi\Core\Http\Actions\Middleware\ItAcceptsJsonApiResponses;
use LaravelJsonApi\Core\Http\Actions\Middleware\ItHasJsonApiContent;
use LaravelJsonApi\Core\Http\Actions\Middleware\LookupModelIfMissing;
use LaravelJsonApi\Core\Http\Actions\Middleware\ValidateQueryOneParameters;
use LaravelJsonApi\Core\Http\Actions\Update\Middleware\AuthorizeUpdateAction;
use LaravelJsonApi\Core\Http\Actions\Update\Middleware\CheckRequestJsonIsCompliant;
use LaravelJsonApi\Core\Http\Actions\Update\Middleware\ParseUpdateOperation;
use LaravelJsonApi\Core\Http\Actions\Update\UpdateActionHandler;
use LaravelJsonApi\Core\Http\Actions\Update\UpdateActionInput;
use LaravelJsonApi\Core\Http\Hooks\HooksImplementation;
use LaravelJsonApi\Core\Query\FieldSets;
use LaravelJsonApi\Core\Query\IncludePaths;
use LaravelJsonApi\Core\Responses\DataResponse;
use LaravelJsonApi\Core\Support\PipelineFactory;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpdateActionHandlerTest extends TestCase
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
     * @var MockObject&QueryDispatcher
     */
    private QueryDispatcher&MockObject $queryDispatcher;

    /**
     * @var UpdateActionHandler
     */
    private UpdateActionHandler $handler;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = new UpdateActionHandler(
            $this->pipelineFactory = $this->createMock(PipelineFactory::class),
            $this->commandDispatcher = $this->createMock(CommandDispatcher::class),
            $this->queryDispatcher = $this->createMock(QueryDispatcher::class),
        );
    }

    /**
     * @return void
     */
    public function testItIsSuccessful(): void
    {
        $request = $this->createMock(Request::class);
        $type = new ResourceType('comments2');
        $id = new ResourceId('123');

        $queryParams = $this->createMock(QueryParameters::class);
        $queryParams->method('includePaths')->willReturn($include = new IncludePaths());
        $queryParams->method('sparseFieldSets')->willReturn($fields = new FieldSets());

        $passed = (new UpdateActionInput($request, $type, $id))
            ->withModel($model = new \stdClass())
            ->withOperation($op = new Update(null, new ResourceObject($type)))
            ->withQuery($queryParams)
            ->withHooks($hooks = new \stdClass());

        $original = $this->willSendThroughPipeline($passed);

        $expected = QueryResult::ok(
            $payload = new Payload(new \stdClass(), true, ['baz' => 'bat']),
            $queryParams,
        );

        $this->commandDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(
                function (UpdateCommand $command) use ($request, $model, $op, $queryParams, $hooks): bool {
                    $this->assertSame($request, $command->request());
                    $this->assertSame($model, $command->model());
                    $this->assertSame($op, $command->operation());
                    $this->assertSame($queryParams, $command->query());
                    $this->assertObjectEquals(new HooksImplementation($hooks), $command->hooks());
                    $this->assertFalse($command->mustAuthorize());
                    $this->assertTrue($command->mustValidate());
                    return true;
                },
            ))
            ->willReturn(CommandResult::ok(new Payload($m = new \stdClass(), true, ['foo' => 'bar'])));

        $this->queryDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(
                function (FetchOneQuery $query) use ($request, $type, $m, $id, $queryParams, $hooks): bool {
                    $this->assertSame($request, $query->request());
                    $this->assertSame($type, $query->type());
                    $this->assertSame($m, $query->model());
                    $this->assertSame($id, $query->id());
                    $this->assertSame($queryParams, $query->toQueryParams());
                    // hooks must be null, otherwise we trigger the "reading" and "read" hooks
                    $this->assertNull($query->hooks());
                    $this->assertFalse($query->mustAuthorize());
                    $this->assertFalse($query->mustValidate());
                    return true;
                },
            ))
            ->willReturn($expected);

        $response = $this->handler->execute($original);

        $this->assertSame($payload->data, $response->data);
        $this->assertSame(['foo' => 'bar', 'baz' => 'bat'], $response->meta->all());
        $this->assertSame($include, $response->includePaths);
        $this->assertSame($fields, $response->fieldSets);
    }

    /**
     * @return void
     */
    public function testItHandlesFailedCommandResult(): void
    {
        $request = $this->createMock(Request::class);
        $type = new ResourceType('comments2');
        $id = new ResourceId('123');

        $passed = (new UpdateActionInput($request, $type, $id))
            ->withModel(new \stdClass())
            ->withOperation(new Update(null, new ResourceObject($type)))
            ->withQuery($this->createMock(QueryParameters::class));

        $original = $this->willSendThroughPipeline($passed);

        $this->commandDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->willReturn(CommandResult::failed($expected = new ErrorList()));

        $this->queryDispatcher
            ->expects($this->never())
            ->method('dispatch');

        try {
            $this->handler->execute($original);
            $this->fail('No exception thrown.');
        } catch (JsonApiException $ex) {
            $this->assertSame($expected, $ex->getErrors());
        }
    }

    /**
     * @return array[]
     */
    public function missingModelCommandResultProvider(): array
    {
        return [
            [new Payload(null, false)],
            [new Payload(null, true)],
        ];
    }

    /**
     * @param Payload $payload
     * @return void
     * @dataProvider missingModelCommandResultProvider
     */
    public function testItPassesOriginalModelIfCommandDoesNotReturnOne(Payload $payload): void
    {
        $request = $this->createMock(Request::class);
        $type = new ResourceType('comments2');
        $id = new ResourceId('456');

        $passed = (new UpdateActionInput($request, $type, $id))
            ->withModel($model = new \stdClass())
            ->withOperation(new Update(null, new ResourceObject($type)))
            ->withQuery($queryParams = $this->createMock(QueryParameters::class));

        $original = $this->willSendThroughPipeline($passed);

        $this->commandDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->willReturn(CommandResult::ok($payload));

        $expected = QueryResult::ok(
            $payload = new Payload(new \stdClass(), true),
            $queryParams,
        );

        $this->queryDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(
                function (FetchOneQuery $query) use ($request, $type, $model, $id, $queryParams): bool {
                    $this->assertSame($request, $query->request());
                    $this->assertSame($type, $query->type());
                    $this->assertSame($model, $query->model());
                    $this->assertSame($id, $query->id());
                    $this->assertSame($queryParams, $query->toQueryParams());
                    // hooks must be null, otherwise we trigger the "reading" and "read" hooks
                    $this->assertNull($query->hooks());
                    $this->assertFalse($query->mustAuthorize());
                    $this->assertFalse($query->mustValidate());
                    return true;
                },
            ))
            ->willReturn($expected);

        $response = $this->handler->execute($original);

        $this->assertSame($payload->data, $response->data);
    }

    /**
     * @return void
     */
    public function testItHandlesFailedQueryResult(): void
    {
        $request = $this->createMock(Request::class);
        $type = new ResourceType('comments2');
        $id = new ResourceId('123');

        $passed = (new UpdateActionInput($request, $type, $id))
            ->withModel(new \stdClass())
            ->withOperation(new Update(null, new ResourceObject($type)))
            ->withQuery($this->createMock(QueryParameters::class));

        $original = $this->willSendThroughPipeline($passed);

        $this->commandDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->willReturn(CommandResult::ok(new Payload(new \stdClass(), true)));

        $this->queryDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->willReturn(QueryResult::failed($expected = new ErrorList()));

        try {
            $this->handler->execute($original);
            $this->fail('No exception thrown.');
        } catch (JsonApiException $ex) {
            $this->assertSame($expected, $ex->getErrors());
        }
    }

    /**
     * @return void
     */
    public function testItHandlesUnexpectedQueryResult(): void
    {
        $request = $this->createMock(Request::class);
        $type = new ResourceType('comments2');
        $id = new ResourceId('123');

        $passed = (new UpdateActionInput($request, $type, $id))
            ->withModel(new \stdClass())
            ->withOperation(new Update(null, new ResourceObject($type)))
            ->withQuery($this->createMock(QueryParameters::class));

        $original = $this->willSendThroughPipeline($passed);

        $this->commandDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->willReturn(CommandResult::ok(new Payload(new \stdClass(), true)));

        $this->queryDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->willReturn(QueryResult::ok(new Payload(null, false)));

        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage('Expecting query result to have data.');

        $this->handler->execute($original);
    }

    /**
     * @param UpdateActionInput $passed
     * @return UpdateActionInput
     */
    private function willSendThroughPipeline(UpdateActionInput $passed): UpdateActionInput
    {
        $original = new UpdateActionInput(
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
                    ItHasJsonApiContent::class,
                    ItAcceptsJsonApiResponses::class,
                    LookupModelIfMissing::class,
                    AuthorizeUpdateAction::class,
                    CheckRequestJsonIsCompliant::class,
                    ValidateQueryOneParameters::class,
                    ParseUpdateOperation::class,
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
            ->willReturnCallback(function (Closure $fn) use ($passed, &$sequence): DataResponse {
                $this->assertSame(['through', 'via'], $sequence);
                return $fn($passed);
            });

        return $original;
    }
}
