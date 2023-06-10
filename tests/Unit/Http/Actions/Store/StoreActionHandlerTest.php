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
use LaravelJsonApi\Core\Bus\Commands\Result as CommandResult;
use LaravelJsonApi\Core\Bus\Commands\Store\StoreCommand;
use LaravelJsonApi\Core\Bus\Queries\FetchOne\FetchOneQuery;
use LaravelJsonApi\Core\Bus\Queries\Result as QueryResult;
use LaravelJsonApi\Core\Document\ErrorList;
use LaravelJsonApi\Core\Document\Input\Values\ResourceObject;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Store;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result as Payload;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Href;
use LaravelJsonApi\Core\Http\Actions\Middleware\ItAcceptsJsonApiResponses;
use LaravelJsonApi\Core\Http\Actions\Middleware\ItHasJsonApiContent;
use LaravelJsonApi\Core\Http\Actions\Middleware\ValidateQueryOneParameters;
use LaravelJsonApi\Core\Http\Actions\Store\Middleware\AuthorizeStoreAction;
use LaravelJsonApi\Core\Http\Actions\Store\Middleware\CheckRequestJsonIsCompliant;
use LaravelJsonApi\Core\Http\Actions\Store\Middleware\ParseStoreOperation;
use LaravelJsonApi\Core\Http\Actions\Store\StoreActionInput;
use LaravelJsonApi\Core\Http\Actions\Store\StoreActionHandler;
use LaravelJsonApi\Core\Http\Controllers\Hooks\HooksImplementation;
use LaravelJsonApi\Core\Query\FieldSets;
use LaravelJsonApi\Core\Query\IncludePaths;
use LaravelJsonApi\Core\Responses\DataResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StoreActionHandlerTest extends TestCase
{
    /**
     * @var Pipeline&MockObject
     */
    private Pipeline&MockObject $pipeline;

    /**
     * @var MockObject&CommandDispatcher
     */
    private CommandDispatcher&MockObject $commandDispatcher;

    /**
     * @var MockObject&QueryDispatcher
     */
    private QueryDispatcher&MockObject $queryDispatcher;

    /**
     * @var StoreActionHandler
     */
    private StoreActionHandler $handler;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = new StoreActionHandler(
            $this->pipeline = $this->createMock(Pipeline::class),
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

        $queryParams = $this->createMock(QueryParameters::class);
        $queryParams->method('includePaths')->willReturn($include = new IncludePaths());
        $queryParams->method('sparseFieldSets')->willReturn($fields = new FieldSets());

        $passed = StoreActionInput::make($request, $type)
            ->withOperation($op = new Store(new Href('/posts'), new ResourceObject($type)))
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

        $this->queryDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(
                function (FetchOneQuery $query) use ($request, $type, $model, $queryParams, $hooks): bool {
                    $this->assertSame($request, $query->request());
                    $this->assertSame($type, $query->type());
                    $this->assertSame($model, $query->model());
                    $this->assertNull($query->id());
                    $this->assertNull($query->modelKey());
                    $this->assertSame($queryParams, $query->toQueryParams());
                    $this->assertObjectEquals(new HooksImplementation($hooks), $query->hooks());
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

        $passed = StoreActionInput::make($request, $type)
            ->withOperation(new Store(new Href('/posts'), new ResourceObject($type)))
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

        $passed = StoreActionInput::make($request, $type)
            ->withOperation(new Store(new Href('/posts'), new ResourceObject($type)))
            ->withQuery($this->createMock(QueryParameters::class));

        $original = $this->willSendThroughPipeline($passed);

        $this->commandDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->willReturn(CommandResult::ok($payload));

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

        $passed = StoreActionInput::make($request, $type)
            ->withOperation(new Store(new Href('/posts'), new ResourceObject($type)))
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

        $passed = StoreActionInput::make($request, $type)
            ->withOperation(new Store(new Href('/posts'), new ResourceObject($type)))
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

        $this->pipeline
            ->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($original))
            ->willReturnCallback(function () use (&$sequence): Pipeline {
                $sequence[] = 'send';
                return $this->pipeline;
            });

        $this->pipeline
            ->expects($this->once())
            ->method('through')
            ->willReturnCallback(function (array $actual) use (&$sequence): Pipeline {
                $sequence[] = 'through';
                $this->assertSame([
                    ItHasJsonApiContent::class,
                    ItAcceptsJsonApiResponses::class,
                    AuthorizeStoreAction::class,
                    CheckRequestJsonIsCompliant::class,
                    ValidateQueryOneParameters::class,
                    ParseStoreOperation::class,
                ], $actual);
                return $this->pipeline;
            });

        $this->pipeline
            ->expects($this->once())
            ->method('via')
            ->with('handle')
            ->willReturnCallback(function () use (&$sequence): Pipeline {
                $sequence[] = 'via';
                return $this->pipeline;
            });

        $this->pipeline
            ->expects($this->once())
            ->method('then')
            ->willReturnCallback(function (Closure $fn) use ($passed, &$sequence): DataResponse {
                $this->assertSame(['send', 'through', 'via'], $sequence);
                return $fn($passed);
            });

        return $original;
    }
}
