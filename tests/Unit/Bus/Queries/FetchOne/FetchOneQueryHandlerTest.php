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

namespace LaravelJsonApi\Core\Tests\Unit\Bus\Queries\FetchOne;

use Closure;
use Illuminate\Contracts\Pipeline\Pipeline;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Query\QueryParameters;
use LaravelJsonApi\Contracts\Store\QueryOneBuilder;
use LaravelJsonApi\Contracts\Store\Store as StoreContract;
use LaravelJsonApi\Core\Bus\Queries\FetchOne\FetchOneQuery;
use LaravelJsonApi\Core\Bus\Queries\FetchOne\FetchOneQueryHandler;
use LaravelJsonApi\Core\Bus\Queries\FetchOne\Middleware\AuthorizeFetchOneQuery;
use LaravelJsonApi\Core\Bus\Queries\FetchOne\Middleware\TriggerShowHooks;
use LaravelJsonApi\Core\Bus\Queries\FetchOne\Middleware\ValidateFetchOneQuery;
use LaravelJsonApi\Core\Bus\Queries\Middleware\LookupModelIfAuthorizing;
use LaravelJsonApi\Core\Bus\Queries\Middleware\LookupResourceIdIfNotSet;
use LaravelJsonApi\Core\Bus\Queries\Result;
use LaravelJsonApi\Core\Document\Input\Values\ResourceId;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FetchOneQueryHandlerTest extends TestCase
{
    /**
     * @var Pipeline&MockObject
     */
    private Pipeline&MockObject $pipeline;

    /**
     * @var MockObject&StoreContract
     */
    private StoreContract&MockObject $store;

    /**
     * @var FetchOneQueryHandler
     */
    private FetchOneQueryHandler $handler;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = new FetchOneQueryHandler(
            $this->pipeline = $this->createMock(Pipeline::class),
            $this->store = $this->createMock(StoreContract::class),
        );
    }

    /**
     * @return void
     */
    public function test(): void
    {
        $original = new FetchOneQuery(
            $request = $this->createMock(Request::class),
            $type = new ResourceType('comments'),
        );

        $passed = FetchOneQuery::make($request, $type)
            ->withValidated($validated = ['include' => 'user'])
            ->withId($id = new ResourceId('123'));

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
                    LookupModelIfAuthorizing::class,
                    AuthorizeFetchOneQuery::class,
                    ValidateFetchOneQuery::class,
                    LookupResourceIdIfNotSet::class,
                    TriggerShowHooks::class,
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
            ->willReturnCallback(function (Closure $fn) use ($passed, &$sequence): Result {
                $this->assertSame(['send', 'through', 'via'], $sequence);
                return $fn($passed);
            });

        $this->store
            ->expects($this->once())
            ->method('queryOne')
            ->with($this->identicalTo($type), $this->identicalTo($id))
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
            ->willReturn($model = new \stdClass());

        $payload = $this->handler
            ->execute($original)
            ->payload();

        $this->assertTrue($payload->hasData);
        $this->assertSame($model, $payload->data);
        $this->assertEmpty($payload->meta);
    }
}
