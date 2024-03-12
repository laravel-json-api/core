<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Bus\Queries\FetchRelated;

use Closure;
use Illuminate\Contracts\Pipeline\Pipeline;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Query\QueryParameters;
use LaravelJsonApi\Contracts\Schema\Container as SchemaContainer;
use LaravelJsonApi\Contracts\Schema\Relation;
use LaravelJsonApi\Contracts\Schema\Schema;
use LaravelJsonApi\Contracts\Store\QueryOneBuilder;
use LaravelJsonApi\Contracts\Store\Store as StoreContract;
use LaravelJsonApi\Core\Bus\Queries\FetchRelated\FetchRelatedQuery;
use LaravelJsonApi\Core\Bus\Queries\FetchRelated\FetchRelatedQueryHandler;
use LaravelJsonApi\Core\Bus\Queries\FetchRelated\Middleware\AuthorizeFetchRelatedQuery;
use LaravelJsonApi\Core\Bus\Queries\FetchRelated\Middleware\TriggerShowRelatedHooks;
use LaravelJsonApi\Core\Bus\Queries\FetchRelated\Middleware\ValidateFetchRelatedQuery;
use LaravelJsonApi\Core\Bus\Queries\Middleware\SetModelIfMissing;
use LaravelJsonApi\Core\Bus\Queries\Result;
use LaravelJsonApi\Core\Query\Input\QueryRelated;
use LaravelJsonApi\Core\Store\QueryManyHandler;
use LaravelJsonApi\Core\Support\PipelineFactory;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FetchRelatedQueryHandlerTest extends TestCase
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
     * @var MockObject&SchemaContainer
     */
    private SchemaContainer&MockObject $schemas;

    /**
     * @var FetchRelatedQueryHandler
     */
    private FetchRelatedQueryHandler $handler;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = new FetchRelatedQueryHandler(
            $this->pipelineFactory = $this->createMock(PipelineFactory::class),
            $this->store = $this->createMock(StoreContract::class),
            $this->schemas = $this->createMock(SchemaContainer::class),
        );
    }

    /**
     * @return void
     */
    public function testItFetchesToOne(): void
    {
        $original = new FetchRelatedQuery(
            $request = $this->createMock(Request::class),
            new QueryRelated(
                $type = new ResourceType('comments'),
                $id = new ResourceId('123'),
                'author',
            ),
        );

        $passed = FetchRelatedQuery::make($request, new QueryRelated($type, $id, $fieldName = 'createdBy'))
            ->withModel($model = new \stdClass())
            ->withValidated($validated = ['include' => 'profile']);

        $this->willSendThroughPipe($original, $passed);
        $this->willSeeRelation($type, $fieldName, toOne: true);

        $this->store
            ->expects($this->once())
            ->method('queryToOne')
            ->with($this->identicalTo($type), $this->identicalTo($id), $this->identicalTo($fieldName))
            ->willReturn($builder = $this->createMock(QueryOneBuilder::class));

        $builder
            ->expects($this->once())
            ->method('withQuery')
            ->with($this->callback(function (QueryParameters $parameters) use ($validated): bool {
                $this->assertSame($validated, $parameters->toQuery());
                return true;
            }))->willReturnSelf();

        $builder
            ->expects($this->once())
            ->method('first')
            ->willReturn($related = new \stdClass());

        $result = $this->handler->execute($original);
        $payload = $result->payload();

        $this->assertSame($model, $result->relatesTo());
        $this->assertSame($fieldName, $result->fieldName());
        $this->assertTrue($payload->hasData);
        $this->assertSame($related, $payload->data);
        $this->assertEmpty($payload->meta);
    }

    /**
     * @return void
     */
    public function testItFetchesToMany(): void
    {
        $original = new FetchRelatedQuery(
            $request = $this->createMock(Request::class),
            new QueryRelated(
                $type = new ResourceType('posts'),
                $id = new ResourceId('123'),
                'comments',
            ),
        );

        $passed = FetchRelatedQuery::make($request, new QueryRelated($type, $id, $fieldName = 'tags'))
            ->withModel($model = new \stdClass())
            ->withValidated($validated = ['include' => 'parent', 'page' => ['number' => 2]]);

        $this->willSendThroughPipe($original, $passed);
        $this->willSeeRelation($type, $fieldName, toOne: false);

        $this->store
            ->expects($this->once())
            ->method('queryToMany')
            ->with($this->identicalTo($type), $this->identicalTo($id), $this->identicalTo($fieldName))
            ->willReturn($builder = $this->createMock(QueryManyHandler::class));

        $builder
            ->expects($this->once())
            ->method('withQuery')
            ->with($this->callback(function (QueryParameters $parameters) use ($validated): bool {
                $this->assertSame($validated, $parameters->toQuery());
                return true;
            }))->willReturnSelf();

        $builder
            ->expects($this->once())
            ->method('getOrPaginate')
            ->with($this->identicalTo($validated['page']))
            ->willReturn($related = [new \stdClass()]);

        $result = $this->handler->execute($original);
        $payload = $result->payload();

        $this->assertSame($model, $result->relatesTo());
        $this->assertSame($fieldName, $result->fieldName());
        $this->assertTrue($payload->hasData);
        $this->assertSame($related, $payload->data);
        $this->assertEmpty($payload->meta);
    }

    /**
     * @param FetchRelatedQuery $original
     * @param FetchRelatedQuery $passed
     * @return void
     */
    private function willSendThroughPipe(FetchRelatedQuery $original, FetchRelatedQuery $passed): void
    {
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
                    AuthorizeFetchRelatedQuery::class,
                    ValidateFetchRelatedQuery::class,
                    TriggerShowRelatedHooks::class,
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
            ->willReturnCallback(function (Closure $fn) use ($passed, &$sequence): Result {
                $this->assertSame(['through', 'via'], $sequence);
                return $fn($passed);
            });
    }

    /**
     * @param ResourceType $type
     * @param string $fieldName
     * @param bool $toOne
     * @return void
     */
    private function willSeeRelation(ResourceType $type, string $fieldName, bool $toOne): void
    {
        $this->schemas
            ->expects($this->once())
            ->method('schemaFor')
            ->with($this->identicalTo($type))
            ->willReturn($schema = $this->createMock(Schema::class));

        $schema
            ->expects($this->once())
            ->method('relationship')
            ->with($this->identicalTo($fieldName))
            ->willReturn($relation = $this->createMock(Relation::class));

        $relation->method('toOne')->willReturn($toOne);
        $relation->method('toMany')->willReturn(!$toOne);
    }
}
