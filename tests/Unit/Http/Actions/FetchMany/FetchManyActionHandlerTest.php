<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
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
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result as Payload;
use LaravelJsonApi\Core\Http\Actions\FetchMany\FetchManyActionHandler;
use LaravelJsonApi\Core\Http\Actions\FetchMany\FetchManyActionInput;
use LaravelJsonApi\Core\Http\Actions\Middleware\ItAcceptsJsonApiResponses;
use LaravelJsonApi\Core\Http\Hooks\HooksImplementation;
use LaravelJsonApi\Core\Query\FieldSets;
use LaravelJsonApi\Core\Query\IncludePaths;
use LaravelJsonApi\Core\Responses\DataResponse;
use LaravelJsonApi\Core\Support\PipelineFactory;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FetchManyActionHandlerTest extends TestCase
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
            $this->pipelineFactory = $this->createMock(PipelineFactory::class),
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

        $passed = (new FetchManyActionInput($request, $type))
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
        $passed = new FetchManyActionInput(
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
        $passed = new FetchManyActionInput(
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
