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

namespace LaravelJsonApi\Core\Tests\Unit\Http\Actions\FetchMany;

use ArrayObject;
use Closure;
use Illuminate\Contracts\Pipeline\Pipeline;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Bus\Queries\Dispatcher;
use LaravelJsonApi\Contracts\Query\QueryParameters;
use LaravelJsonApi\Core\Bus\Queries\FetchMany\FetchManyQuery;
use LaravelJsonApi\Core\Bus\Queries\Result;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result as Payload;
use LaravelJsonApi\Core\Http\Actions\FetchMany\FetchManyActionHandler;
use LaravelJsonApi\Core\Http\Actions\FetchMany\FetchManyActionInput;
use LaravelJsonApi\Core\Http\Actions\Middleware\ItAcceptsJsonApiResponses;
use LaravelJsonApi\Core\Http\Controllers\Hooks\HooksImplementation;
use LaravelJsonApi\Core\Query\FieldSets;
use LaravelJsonApi\Core\Query\IncludePaths;
use LaravelJsonApi\Core\Responses\DataResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FetchManyActionHandlerTest extends TestCase
{
    /**
     * @var Pipeline&MockObject
     */
    private Pipeline&MockObject $pipeline;

    /**
     * @var MockObject&Dispatcher
     */
    private Dispatcher&MockObject $dispatcher;

    /**
     * @var FetchManyActionHandler
     */
    private FetchManyActionHandler $handler;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = new FetchManyActionHandler(
            $this->pipeline = $this->createMock(Pipeline::class),
            $this->dispatcher = $this->createMock(Dispatcher::class),
        );
    }

    /**
     * @return void
     */
    public function testItIsSuccessful(): void
    {
        $request = $this->createMock(Request::class);
        $type = new ResourceType('comments2');

        $passed = FetchManyActionInput::make($request, $type)
            ->withHooks($hooks = new \stdClass);

        $original = $this->willSendThroughPipeline($passed);

        $queryParams = $this->createMock(QueryParameters::class);
        $queryParams->method('includePaths')->willReturn($include = new IncludePaths());
        $queryParams->method('sparseFieldSets')->willReturn($fields = new FieldSets());

        $expected = Result::ok(
            $payload = new Payload(new ArrayObject(), true, ['foo' => 'bar']),
            $queryParams,
        );

        $this->dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (FetchManyQuery $query) use ($request, $type, $hooks): bool {
                $this->assertSame($request, $query->request());
                $this->assertSame($type, $query->type());
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
    public function testItIsNotSuccessful(): void
    {
        $passed = FetchManyActionInput::make(
            $this->createMock(Request::class),
            new ResourceType('comments2'),
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
        $passed = FetchManyActionInput::make(
            $this->createMock(Request::class),
            new ResourceType('comments2'),
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
     * @param FetchManyActionInput $passed
     * @return FetchManyActionInput
     */
    private function willSendThroughPipeline(FetchManyActionInput $passed): FetchManyActionInput
    {
        $original = new FetchManyActionInput(
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
                    ItAcceptsJsonApiResponses::class,
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
