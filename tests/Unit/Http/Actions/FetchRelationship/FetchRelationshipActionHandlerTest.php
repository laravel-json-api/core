<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Http\Actions\FetchRelationship;

use Closure;
use Illuminate\Contracts\Pipeline\Pipeline;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Bus\Queries\Dispatcher;
use LaravelJsonApi\Contracts\Query\QueryParameters;
use LaravelJsonApi\Core\Bus\Queries\FetchRelationship\FetchRelationshipQuery;
use LaravelJsonApi\Core\Bus\Queries\Result;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result as Payload;
use LaravelJsonApi\Core\Http\Actions\FetchRelationship\FetchRelationshipActionHandler;
use LaravelJsonApi\Core\Http\Actions\FetchRelationship\FetchRelationshipActionInput;
use LaravelJsonApi\Core\Http\Actions\Middleware\ItAcceptsJsonApiResponses;
use LaravelJsonApi\Core\Http\Hooks\HooksImplementation;
use LaravelJsonApi\Core\Query\FieldSets;
use LaravelJsonApi\Core\Query\IncludePaths;
use LaravelJsonApi\Core\Responses\RelationshipResponse;
use LaravelJsonApi\Core\Support\PipelineFactory;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FetchRelationshipActionHandlerTest extends TestCase
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
     * @var FetchRelationshipActionHandler
     */
    private FetchRelationshipActionHandler $handler;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = new FetchRelationshipActionHandler(
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
        $type = new ResourceType('posts');
        $id = new ResourceId('123');

        $passed = (new FetchRelationshipActionInput($request, $type, $id, 'comments1'))
            ->withHooks($hooks = new \stdClass);

        $original = $this->willSendThroughPipeline($passed);

        $queryParams = $this->createMock(QueryParameters::class);
        $queryParams->method('includePaths')->willReturn($include = new IncludePaths());
        $queryParams->method('sparseFieldSets')->willReturn($fields = new FieldSets());

        $expected = Result::ok(
            $payload = new Payload(new \stdClass(), true, ['foo' => 'bar']),
            $queryParams,
        )->withRelatedTo($model = new \stdClass(), 'comments2');

        $this->dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (FetchRelationshipQuery $query) use ($request, $type, $id, $hooks): bool {
                $this->assertSame($request, $query->request());
                $this->assertSame($type, $query->type());
                $this->assertSame($id, $query->id());
                $this->assertSame('comments1', $query->fieldName());
                $this->assertNull($query->model());
                $this->assertTrue($query->mustAuthorize());
                $this->assertTrue($query->mustValidate());
                $this->assertObjectEquals(new HooksImplementation($hooks), $query->hooks());
                return true;
            }))
            ->willReturn($expected);

        $response = $this->handler->execute($original);

        $this->assertSame($model, $response->model);
        $this->assertSame('comments2', $response->fieldName);
        $this->assertSame($payload->data, $response->related);
        $this->assertSame($payload->meta, $response->meta->all());
        $this->assertSame($include, $response->includePaths);
        $this->assertSame($fields, $response->fieldSets);
    }

    /**
     * @return void
     */
    public function testItIsSuccessfulWithModel(): void
    {
        $passed = (new FetchRelationshipActionInput(
            $request = $this->createMock(Request::class),
            $type = new ResourceType('posts'),
            $id = new ResourceId('123'),
            'comments1',
        ))->withModel($model1 = new \stdClass());

        $original = $this->willSendThroughPipeline($passed);

        $expected = Result::ok($payload = new Payload([new \stdClass()], true))
            ->withRelatedTo($model2 = new \stdClass(), 'comments2');

        $this->dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (FetchRelationshipQuery $query) use ($request, $type, $id, $model1): bool {
                $this->assertSame($request, $query->request());
                $this->assertSame($type, $query->type());
                $this->assertSame($id, $query->id());
                $this->assertSame($model1, $query->model());
                $this->assertSame('comments1', $query->fieldName());
                $this->assertTrue($query->mustAuthorize());
                $this->assertTrue($query->mustValidate());
                $this->assertNull($query->hooks());
                return true;
            }))
            ->willReturn($expected);

        $response = $this->handler->execute($original);

        $this->assertSame($model2, $response->model);
        $this->assertSame('comments2', $response->fieldName);
        $this->assertSame($payload->data, $response->related);
        $this->assertEmpty($response->meta);
        $this->assertNull($response->includePaths);
        $this->assertNull($response->fieldSets);
    }

    /**
     * @return void
     */
    public function testItIsNotSuccessful(): void
    {
        $passed = new FetchRelationshipActionInput(
            $this->createMock(Request::class),
            new ResourceType('posts'),
            new ResourceId('123'),
            'tags',
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
        $passed = new FetchRelationshipActionInput(
            $this->createMock(Request::class),
            new ResourceType('posts'),
            new ResourceId('123'),
            'tags',
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
     * @param FetchRelationshipActionInput $passed
     * @return FetchRelationshipActionInput
     */
    private function willSendThroughPipeline(FetchRelationshipActionInput $passed): FetchRelationshipActionInput
    {
        $original = new FetchRelationshipActionInput(
            $this->createMock(Request::class),
            new ResourceType('foobar'),
            new ResourceId('999'),
            'bazbat',
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
            ->willReturnCallback(function (Closure $fn) use ($passed, &$sequence): RelationshipResponse {
                $this->assertSame(['through', 'via'], $sequence);
                return $fn($passed);
            });

        return $original;
    }
}
