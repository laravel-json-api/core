<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Bus\Queries\FetchMany;

use Closure;
use Illuminate\Contracts\Pipeline\Pipeline;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Query\QueryParameters;
use LaravelJsonApi\Contracts\Store\Store as StoreContract;
use LaravelJsonApi\Core\Bus\Queries\FetchMany\FetchManyQuery;
use LaravelJsonApi\Core\Bus\Queries\FetchMany\FetchManyQueryHandler;
use LaravelJsonApi\Core\Bus\Queries\FetchMany\Middleware\AuthorizeFetchManyQuery;
use LaravelJsonApi\Core\Bus\Queries\FetchMany\Middleware\TriggerIndexHooks;
use LaravelJsonApi\Core\Bus\Queries\FetchMany\Middleware\ValidateFetchManyQuery;
use LaravelJsonApi\Core\Bus\Queries\Result;
use LaravelJsonApi\Core\Query\Input\QueryMany;
use LaravelJsonApi\Core\Store\QueryAllHandler;
use LaravelJsonApi\Core\Support\PipelineFactory;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FetchManyQueryHandlerTest extends TestCase
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
     * @var FetchManyQueryHandler
     */
    private FetchManyQueryHandler $handler;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = new FetchManyQueryHandler(
            $this->pipelineFactory = $this->createMock(PipelineFactory::class),
            $this->store = $this->createMock(StoreContract::class),
        );
    }

    /**
     * @return void
     */
    public function test(): void
    {
        $original = new FetchManyQuery(
            $request = $this->createMock(Request::class),
            $input = new QueryMany($type = new ResourceType('comments')),
        );

        $passed = FetchManyQuery::make($request, $input)
            ->withValidated($validated = ['include' => 'user', 'page' => ['number' => 2]]);

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
                    AuthorizeFetchManyQuery::class,
                    ValidateFetchManyQuery::class,
                    TriggerIndexHooks::class,
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

        $this->store
            ->expects($this->once())
            ->method('queryAll')
            ->with($this->identicalTo($type))
            ->willReturn($builder = $this->createMock(QueryAllHandler::class));

        $builder
            ->expects($this->once())
            ->method('withQuery')
            ->with($this->callback(function (QueryParameters $parameters) use ($validated): bool {
                $this->assertSame($validated, $parameters->toQuery());
                return true;
            }))->willReturnSelf();

        $builder
            ->expects($this->once())
            ->method('firstOrPaginate')
            ->with($this->identicalTo($validated['page']))
            ->willReturn($models = [new \stdClass()]);

        $payload = $this->handler
            ->execute($original)
            ->payload();

        $this->assertTrue($payload->hasData);
        $this->assertSame($models, $payload->data);
        $this->assertEmpty($payload->meta);
    }
}
