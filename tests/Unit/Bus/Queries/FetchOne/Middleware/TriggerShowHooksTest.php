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

namespace LaravelJsonApi\Core\Tests\Unit\Bus\Queries\FetchOne\Middleware;

use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Http\Hooks\ShowImplementation;
use LaravelJsonApi\Core\Bus\Queries\FetchOne\FetchOneQuery;
use LaravelJsonApi\Core\Bus\Queries\FetchOne\Middleware\TriggerShowHooks;
use LaravelJsonApi\Core\Bus\Queries\Result;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result as Payload;
use LaravelJsonApi\Core\Query\Input\QueryOne;
use LaravelJsonApi\Core\Query\QueryParameters;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\TestCase;

class TriggerShowHooksTest extends TestCase
{
    /**
     * @var QueryParameters
     */
    private QueryParameters $queryParameters;

    /**
     * @var TriggerShowHooks
     */
    private TriggerShowHooks $middleware;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->queryParameters = QueryParameters::fromArray([
            'include' => 'author,tags',
        ]);
        $this->middleware = new TriggerShowHooks();
    }

    /**
     * @return void
     */
    public function testItHasNoHooks(): void
    {
        $request = $this->createMock(Request::class);
        $input = new QueryOne(new ResourceType('tags'), new ResourceId('123'));
        $query = FetchOneQuery::make($request, $input);

        $expected = Result::ok(
            new Payload(null, true),
        );

        $actual = $this->middleware->handle(
            $query,
            function (FetchOneQuery $passed) use ($query, $expected): Result {
                $this->assertSame($query, $passed);
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
    }

    /**
     * @return void
     */
    public function testItTriggersHooks(): void
    {
        $request = $this->createMock(Request::class);
        $hooks = $this->createMock(ShowImplementation::class);
        $model = new \stdClass();
        $sequence = [];

        $input = new QueryOne(new ResourceType('tags'), new ResourceId('123'));
        $query = FetchOneQuery::make($request, $input)
            ->withValidated($this->queryParameters->toQuery())
            ->withHooks($hooks);

        $hooks
            ->expects($this->once())
            ->method('reading')
            ->willReturnCallback(function ($req, $q) use (&$sequence, $request): void {
                $sequence[] = 'reading';
                $this->assertSame($request, $req);
                $this->assertEquals($this->queryParameters, $q);
            });

        $hooks
            ->expects($this->once())
            ->method('read')
            ->willReturnCallback(function ($m, $req, $q) use (&$sequence, $model, $request): void {
                $sequence[] = 'read';
                $this->assertSame($model, $m);
                $this->assertSame($request, $req);
                $this->assertEquals($this->queryParameters, $q);
            });

        $expected = Result::ok(
            new Payload($model, true),
            $this->createMock(QueryParameters::class),
        );

        $actual = $this->middleware->handle(
            $query,
            function (FetchOneQuery $passed) use ($query, $expected, &$sequence): Result {
                $this->assertSame($query, $passed);
                $this->assertSame(['reading'], $sequence);
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
        $this->assertSame(['reading', 'read'], $sequence);
    }

    /**
     * @return void
     */
    public function testItDoesNotTriggerReadHookOnFailure(): void
    {
        $request = $this->createMock(Request::class);
        $hooks = $this->createMock(ShowImplementation::class);
        $sequence = [];

        $input = new QueryOne(new ResourceType('tags'), new ResourceId('123'));
        $query = FetchOneQuery::make($request, $input)
            ->withValidated($this->queryParameters->toQuery())
            ->withHooks($hooks);

        $hooks
            ->expects($this->once())
            ->method('reading')
            ->willReturnCallback(function ($req, $q) use (&$sequence, $request): void {
                $sequence[] = 'reading';
                $this->assertSame($request, $req);
                $this->assertEquals($this->queryParameters, $q);
            });

        $hooks
            ->expects($this->never())
            ->method('read');

        $expected = Result::failed();

        $actual = $this->middleware->handle(
            $query,
            function (FetchOneQuery $passed) use ($query, $expected, &$sequence): Result {
                $this->assertSame($query, $passed);
                $this->assertSame(['reading'], $sequence);
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
        $this->assertSame(['reading'], $sequence);
    }
}
