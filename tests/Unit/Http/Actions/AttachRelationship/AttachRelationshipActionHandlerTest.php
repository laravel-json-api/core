<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Http\Actions\AttachRelationship;

use Closure;
use Illuminate\Contracts\Pipeline\Pipeline;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Bus\Commands\Dispatcher as CommandDispatcher;
use LaravelJsonApi\Contracts\Bus\Queries\Dispatcher as QueryDispatcher;
use LaravelJsonApi\Contracts\Query\QueryParameters;
use LaravelJsonApi\Core\Bus\Commands\AttachRelationship\AttachRelationshipCommand;
use LaravelJsonApi\Core\Bus\Commands\Result as CommandResult;
use LaravelJsonApi\Core\Bus\Queries\FetchRelationship\FetchRelationshipQuery;
use LaravelJsonApi\Core\Bus\Queries\Result as QueryResult;
use LaravelJsonApi\Core\Document\ErrorList;
use LaravelJsonApi\Core\Document\Input\Values\ListOfResourceIdentifiers;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\UpdateToMany;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result as Payload;
use LaravelJsonApi\Core\Extensions\Atomic\Values\OpCodeEnum;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Ref;
use LaravelJsonApi\Core\Http\Actions\AttachRelationship\AttachRelationshipActionHandler;
use LaravelJsonApi\Core\Http\Actions\AttachRelationship\AttachRelationshipActionInput;
use LaravelJsonApi\Core\Http\Actions\AttachRelationship\Middleware\AuthorizeAttachRelationshipAction;
use LaravelJsonApi\Core\Http\Actions\AttachRelationship\Middleware\ParseAttachRelationshipOperation;
use LaravelJsonApi\Core\Http\Actions\Middleware\CheckRelationshipJsonIsCompliant;
use LaravelJsonApi\Core\Http\Actions\Middleware\ItAcceptsJsonApiResponses;
use LaravelJsonApi\Core\Http\Actions\Middleware\ItHasJsonApiContent;
use LaravelJsonApi\Core\Http\Actions\Middleware\LookupModelIfMissing;
use LaravelJsonApi\Core\Http\Actions\Middleware\ValidateRelationshipQueryParameters;
use LaravelJsonApi\Core\Http\Hooks\HooksImplementation;
use LaravelJsonApi\Core\Query\FieldSets;
use LaravelJsonApi\Core\Query\IncludePaths;
use LaravelJsonApi\Core\Responses\RelationshipResponse;
use LaravelJsonApi\Core\Support\PipelineFactory;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AttachRelationshipActionHandlerTest extends TestCase
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
     * @var AttachRelationshipActionHandler
     */
    private AttachRelationshipActionHandler $handler;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = new AttachRelationshipActionHandler(
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
        $fieldName = 'user';

        $queryParams = $this->createMock(QueryParameters::class);
        $queryParams->method('includePaths')->willReturn($include = new IncludePaths());
        $queryParams->method('sparseFieldSets')->willReturn($fields = new FieldSets());

        $op = new UpdateToMany(
            OpCodeEnum::Add,
            new Ref(type: $type, id: $id, relationship: $fieldName),
            new ListOfResourceIdentifiers(),
        );

        $passed = (new AttachRelationshipActionInput($request, $type, $id, $fieldName))
            ->withModel($model = new \stdClass())
            ->withOperation($op)
            ->withQueryParameters($queryParams)
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
                function (AttachRelationshipCommand $command)
                use ($request, $model, $id, $fieldName, $op, $queryParams, $hooks): bool {
                    $this->assertSame($request, $command->request());
                    $this->assertSame($model, $command->model());
                    $this->assertSame($id, $command->id());
                    $this->assertSame($fieldName, $command->fieldName());
                    $this->assertSame($op, $command->operation());
                    $this->assertSame($queryParams, $command->query());
                    $this->assertObjectEquals(new HooksImplementation($hooks), $command->hooks());
                    $this->assertFalse($command->mustAuthorize());
                    $this->assertTrue($command->mustValidate());
                    return true;
                },
            ))
            ->willReturn(CommandResult::ok(new Payload(new \stdClass(), true, ['foo' => 'bar'])));

        $this->queryDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(
                function (FetchRelationshipQuery $query)
                use ($request, $type, $model, $id, $fieldName, $queryParams, $hooks): bool {
                    $this->assertSame($request, $query->request());
                    $this->assertSame($type, $query->type());
                    $this->assertSame($model, $query->model());
                    $this->assertSame($id, $query->id());
                    $this->assertSame($fieldName, $query->fieldName());
                    $this->assertSame($queryParams, $query->toQueryParams());
                    // hooks must be null, otherwise we trigger the reading relationship hooks
                    $this->assertNull($query->hooks());
                    $this->assertFalse($query->mustAuthorize());
                    $this->assertFalse($query->mustValidate());
                    return true;
                },
            ))
            ->willReturn($expected);

        $response = $this->handler->execute($original);

        $this->assertInstanceOf(RelationshipResponse::class, $response);
        $this->assertSame($model, $response->model);
        $this->assertSame($fieldName, $response->fieldName);
        $this->assertSame($payload->data, $response->related);
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
        $fieldName = 'user';

        $op = new UpdateToMany(
            OpCodeEnum::Add,
            new Ref(type: $type, id: $id, relationship: $fieldName),
            new ListOfResourceIdentifiers(),
        );

        $passed = (new AttachRelationshipActionInput($request, $type, $id, $fieldName))
            ->withModel(new \stdClass())
            ->withOperation($op)
            ->withQueryParameters($this->createMock(QueryParameters::class));

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
     * @return void
     */
    public function testItHandlesFailedQueryResult(): void
    {
        $request = $this->createMock(Request::class);
        $type = new ResourceType('comments2');
        $id = new ResourceId('123');
        $fieldName = 'author';

        $op = new UpdateToMany(
            OpCodeEnum::Add,
            new Ref(type: $type, id: $id, relationship: $fieldName),
            new ListOfResourceIdentifiers(),
        );

        $passed = (new AttachRelationshipActionInput($request, $type, $id, $fieldName))
            ->withModel(new \stdClass())
            ->withOperation($op)
            ->withQueryParameters($this->createMock(QueryParameters::class));

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
        $fieldName = 'author';

        $op = new UpdateToMany(
            OpCodeEnum::Add,
            new Ref(type: $type, id: $id, relationship: $fieldName),
            new ListOfResourceIdentifiers(),
        );

        $passed = (new AttachRelationshipActionInput($request, $type, $id, $fieldName))
            ->withModel(new \stdClass())
            ->withOperation($op)
            ->withQueryParameters($this->createMock(QueryParameters::class));

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
     * @param AttachRelationshipActionInput $passed
     * @return AttachRelationshipActionInput
     */
    private function willSendThroughPipeline(AttachRelationshipActionInput $passed): AttachRelationshipActionInput
    {
        $original = new AttachRelationshipActionInput(
            $this->createMock(Request::class),
            new ResourceType('comments1'),
            new ResourceId('123'),
            'foobar',
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
                    AuthorizeAttachRelationshipAction::class,
                    CheckRelationshipJsonIsCompliant::class,
                    ValidateRelationshipQueryParameters::class,
                    ParseAttachRelationshipOperation::class,
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
