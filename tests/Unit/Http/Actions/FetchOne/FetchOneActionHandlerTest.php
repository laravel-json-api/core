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

namespace LaravelJsonApi\Core\Tests\Unit\Http\Actions\FetchOne;

use Closure;
use Illuminate\Contracts\Pipeline\Pipeline;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Bus\Queries\Dispatcher;
use LaravelJsonApi\Contracts\Query\QueryParameters;
use LaravelJsonApi\Core\Bus\Queries\FetchOne\FetchOneQuery;
use LaravelJsonApi\Core\Bus\Queries\Result;
use LaravelJsonApi\Core\Document\Input\Values\ResourceId;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result as Payload;
use LaravelJsonApi\Core\Http\Actions\FetchOne\FetchOneActionHandler;
use LaravelJsonApi\Core\Http\Actions\FetchOne\FetchOneActionInput;
use LaravelJsonApi\Core\Http\Actions\Middleware\ItAcceptsJsonApiResponses;
use LaravelJsonApi\Core\Http\Hooks\HooksImplementation;
use LaravelJsonApi\Core\Query\FieldSets;
use LaravelJsonApi\Core\Query\IncludePaths;
use LaravelJsonApi\Core\Responses\DataResponse;
use LaravelJsonApi\Core\Support\PipelineFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FetchOneActionHandlerTest extends TestCase
{
    /**
     * @var PipelineFactory&MockObject
     */
    private PipelineFactory&MockObject $pipelineFactory;

    /**
     * @var MockObject&Dispatcher
     */
    private Dispatcher&MockObject $dispatcher;

    /**
     * @var FetchOneActionHandler
     */
    private FetchOneActionHandler $handler;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = new FetchOneActionHandler(
            $this->pipelineFactory = $this->createMock(PipelineFactory::class),
            $this->dispatcher = $this->createMock(Dispatcher::class),
        );
    }

    /**
     * @return void
     */
    public function testItIsSuccessfulWithId(): void
    {
        $request = $this->createMock(Request::class);
        $type = new ResourceType('comments2');
        $id = new ResourceId('123');

        $passed = (new FetchOneActionInput($request, $type, $id))
            ->withHooks($hooks = new \stdClass);

        $original = $this->willSendThroughPipeline($passed);

        $queryParams = $this->createMock(QueryParameters::class);
        $queryParams->method('includePaths')->willReturn($include = new IncludePaths());
        $queryParams->method('sparseFieldSets')->willReturn($fields = new FieldSets());

        $expected = Result::ok(
            $payload = new Payload(new \stdClass(), true, ['foo' => 'bar']),
            $queryParams,
        );

        $this->dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (FetchOneQuery $query) use ($request, $type, $id, $hooks): bool {
                $this->assertSame($request, $query->request());
                $this->assertSame($type, $query->type());
                $this->assertSame($id, $query->id());
                $this->assertNull($query->model());
                $this->assertTrue($query->mustAuthorize());
                $this->assertTrue($query->mustValidate());
                $this->assertObjectEquals(new HooksImplementation($hooks), $query->hooks());
                return true;
            }))
            ->willReturn($expected);

        $response = $this->handler->execute($original);

        $this->assertSame($payload->data, $response->data);
        $this->assertSame($payload->meta, $response->meta->all());
        $this->assertSame($include, $response->includePaths);
        $this->assertSame($fields, $response->fieldSets);
    }

    /**
     * @return void
     */
    public function testItIsSuccessfulWithModel(): void
    {
        $passed = (new FetchOneActionInput(
            $request = $this->createMock(Request::class),
            $type = new ResourceType('comments2'),
            $id = new ResourceId('123'),
        ))->withModel($model1 = new \stdClass());

        $original = $this->willSendThroughPipeline($passed);

        $expected = Result::ok(
            new Payload($model2 = new \stdClass(), true),
        );

        $this->dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (FetchOneQuery $query) use ($request, $type, $id, $model1): bool {
                $this->assertSame($request, $query->request());
                $this->assertSame($type, $query->type());
                $this->assertSame($id, $query->id());
                $this->assertSame($model1, $query->model());
                $this->assertTrue($query->mustAuthorize());
                $this->assertTrue($query->mustValidate());
                $this->assertNull($query->hooks());
                return true;
            }))
            ->willReturn($expected);

        $response = $this->handler->execute($original);

        $this->assertSame($model2, $response->data);
        $this->assertEmpty($response->meta);
        $this->assertNull($response->includePaths);
        $this->assertNull($response->fieldSets);
    }

    /**
     * @return void
     */
    public function testItIsNotSuccessful(): void
    {
        $passed = new FetchOneActionInput(
            $this->createMock(Request::class),
            new ResourceType('comments2'),
            new ResourceId('123'),
        );

        $original = $this->willSendThroughPipeline($passed);

        $expected = Result::failed();

        $this->dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->willReturn($expected);

        try {
            $this->handler->execute($original);
            $this->fail('No exception thrown.');
        } catch (JsonApiException $ex) {
            $this->assertSame($expected->errors(), $ex->getErrors());
        }
    }

    /**
     * @return void
     */
    public function testItDoesNotReturnData(): void
    {
        $passed = new FetchOneActionInput(
            $this->createMock(Request::class),
            new ResourceType('comments2'),
            new ResourceId('123'),
        );

        $original = $this->willSendThroughPipeline($passed);

        $expected = Result::ok(new Payload(null, false));

        $this->dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->willReturn($expected);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Expecting query result to have data.');

        $this->handler->execute($original);
    }

    /**
     * @param FetchOneActionInput $passed
     * @return FetchOneActionInput
     */
    private function willSendThroughPipeline(FetchOneActionInput $passed): FetchOneActionInput
    {
        $original = new FetchOneActionInput(
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
