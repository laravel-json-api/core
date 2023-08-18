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

namespace LaravelJsonApi\Core\Tests\Unit\Http\Actions\Store;

use Closure;
use Illuminate\Contracts\Pipeline\Pipeline;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Bus\Commands\Dispatcher as CommandDispatcher;
use LaravelJsonApi\Contracts\Bus\Queries\Dispatcher as QueryDispatcher;
use LaravelJsonApi\Contracts\Query\QueryParameters;
use LaravelJsonApi\Contracts\Resources\Container;
use LaravelJsonApi\Core\Bus\Commands\Result as CommandResult;
use LaravelJsonApi\Core\Bus\Commands\Store\StoreCommand;
use LaravelJsonApi\Core\Bus\Queries\FetchOne\FetchOneQuery;
use LaravelJsonApi\Core\Bus\Queries\Result as QueryResult;
use LaravelJsonApi\Core\Document\ErrorList;
use LaravelJsonApi\Core\Document\Input\Values\ResourceId;
use LaravelJsonApi\Core\Document\Input\Values\ResourceObject;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Create;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result as Payload;
use LaravelJsonApi\Core\Http\Actions\Middleware\ItAcceptsJsonApiResponses;
use LaravelJsonApi\Core\Http\Actions\Middleware\ItHasJsonApiContent;
use LaravelJsonApi\Core\Http\Actions\Middleware\ValidateQueryOneParameters;
use LaravelJsonApi\Core\Http\Actions\Store\Middleware\AuthorizeStoreAction;
use LaravelJsonApi\Core\Http\Actions\Store\Middleware\CheckRequestJsonIsCompliant;
use LaravelJsonApi\Core\Http\Actions\Store\Middleware\ParseStoreOperation;
use LaravelJsonApi\Core\Http\Actions\Store\StoreActionHandler;
use LaravelJsonApi\Core\Http\Actions\Store\StoreActionInput;
use LaravelJsonApi\Core\Http\Hooks\HooksImplementation;
use LaravelJsonApi\Core\Query\FieldSets;
use LaravelJsonApi\Core\Query\IncludePaths;
use LaravelJsonApi\Core\Responses\DataResponse;
use LaravelJsonApi\Core\Support\PipelineFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StoreActionHandlerTest extends TestCase
{
    /**
     * @var PipelineFactory&MockObject
     */
    private readonly PipelineFactory&MockObject $pipelineFactory;

    /**
     * @var MockObject&CommandDispatcher
     */
    private readonly CommandDispatcher&MockObject $commandDispatcher;

    /**
     * @var MockObject&QueryDispatcher
     */
    private readonly QueryDispatcher&MockObject $queryDispatcher;

    /**
     * @var MockObject&Container
     */
    private readonly Container&MockObject $resources;

    /**
     * @var StoreActionHandler
     */
    private readonly StoreActionHandler $handler;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = new StoreActionHandler(
            $this->pipelineFactory = $this->createMock(PipelineFactory::class),
            $this->commandDispatcher = $this->createMock(CommandDispatcher::class),
            $this->queryDispatcher = $this->createMock(QueryDispatcher::class),
            $this->resources = $this->createMock(Container::class),
        );
    }

    /**
     * @return void
     */
    public function testItIsSuccessful(): void
    {
        $request = $this->createMock(Request::class);
        $type = new ResourceType('comments2');

        $queryParams = $this->createMock(QueryParameters::class);
        $queryParams->method('includePaths')->willReturn($include = new IncludePaths());
        $queryParams->method('sparseFieldSets')->willReturn($fields = new FieldSets());

        $passed = (new StoreActionInput($request, $type))
            ->withOperation($op = new Create(null, new ResourceObject($type)))
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
            ->with($this->callback(function (StoreCommand $command) use ($request, $op, $queryParams, $hooks): bool {
                $this->assertSame($request, $command->request());
                $this->assertSame($op, $command->operation());
                $this->assertSame($queryParams, $command->query());
                $this->assertObjectEquals(new HooksImplementation($hooks), $command->hooks());
                $this->assertFalse($command->mustAuthorize());
                $this->assertTrue($command->mustValidate());
                return true;
            }))
            ->willReturn(CommandResult::ok(new Payload($model = new \stdClass(), true, ['foo' => 'bar'])));

        $id = $this->willLookupId($type, $model);

        $this->queryDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(
                function (FetchOneQuery $query) use ($request, $type, $id, $model, $queryParams, $hooks): bool {
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

        $passed = (new StoreActionInput($request, $type))
            ->withOperation(new Create(null, new ResourceObject($type)))
            ->withQuery($this->createMock(QueryParameters::class));

        $original = $this->willSendThroughPipeline($passed);

        $this->commandDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->willReturn(CommandResult::failed($expected = new ErrorList()));

        $this->willNotLookupId();

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
    public function unexpectedCommandResultProvider(): array
    {
        return [
            [new Payload(null, false)],
            [new Payload(null, true)],
        ];
    }

    /**
     * @param Payload $payload
     * @return void
     * @dataProvider unexpectedCommandResultProvider
     */
    public function testItHandlesUnexpectedCommandResult(Payload $payload): void
    {
        $request = $this->createMock(Request::class);
        $type = new ResourceType('comments2');

        $passed = (new StoreActionInput($request, $type))
            ->withOperation(new Create(null, new ResourceObject($type)))
            ->withQuery($this->createMock(QueryParameters::class));

        $original = $this->willSendThroughPipeline($passed);

        $this->commandDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->willReturn(CommandResult::ok($payload));

        $this->willNotLookupId();

        $this->queryDispatcher
            ->expects($this->never())
            ->method('dispatch');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Expecting command result to have an object as data.');

        $this->handler->execute($original);
    }

    /**
     * @return void
     */
    public function testItHandlesFailedQueryResult(): void
    {
        $request = $this->createMock(Request::class);
        $type = new ResourceType('comments2');

        $passed = (new StoreActionInput($request, $type))
            ->withOperation(new Create(null, new ResourceObject($type)))
            ->withQuery($this->createMock(QueryParameters::class));

        $original = $this->willSendThroughPipeline($passed);

        $this->commandDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->willReturn(CommandResult::ok(new Payload($model = new \stdClass(), true)));

        $this->willLookupId($type, $model);

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

        $passed = (new StoreActionInput($request, $type))
            ->withOperation(new Create(null, new ResourceObject($type)))
            ->withQuery($this->createMock(QueryParameters::class));

        $original = $this->willSendThroughPipeline($passed);

        $this->commandDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->willReturn(CommandResult::ok(new Payload($model = new \stdClass(), true)));

        $this->willLookupId($type, $model);

        $this->queryDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->willReturn(QueryResult::ok(new Payload(null, false)));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Expecting query result to have data.');

        $this->handler->execute($original);
    }

    /**
     * @param StoreActionInput $passed
     * @return StoreActionInput
     */
    private function willSendThroughPipeline(StoreActionInput $passed): StoreActionInput
    {
        $original = new StoreActionInput(
            $this->createMock(Request::class),
            new ResourceType('comments1'),
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
                    AuthorizeStoreAction::class,
                    CheckRequestJsonIsCompliant::class,
                    ValidateQueryOneParameters::class,
                    ParseStoreOperation::class,
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

    /**
     * @param ResourceType $type
     * @param object $model
     * @return ResourceId
     */
    private function willLookupId(ResourceType $type, object $model): ResourceId
    {
        $this->resources
            ->expects($this->once())
            ->method('idForType')
            ->with($this->identicalTo($type), $this->identicalTo($model))
            ->willReturn($id = new ResourceId('999'));

        return $id;
    }

    /**
     * @return void
     */
    private function willNotLookupId(): void
    {
        $this->resources
            ->expects($this->never())
            ->method($this->anything());
    }
}
