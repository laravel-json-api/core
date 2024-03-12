<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Bus\Queries\FetchMany\Middleware;

use ArrayIterator;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Http\Hooks\IndexImplementation;
use LaravelJsonApi\Core\Bus\Queries\FetchMany\FetchManyQuery;
use LaravelJsonApi\Core\Bus\Queries\FetchMany\Middleware\TriggerIndexHooks;
use LaravelJsonApi\Core\Bus\Queries\Result;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result as Payload;
use LaravelJsonApi\Core\Query\Input\QueryMany;
use LaravelJsonApi\Core\Query\QueryParameters;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\TestCase;

class TriggerIndexHooksTest extends TestCase
{
    /**
     * @var QueryParameters
     */
    private QueryParameters $queryParameters;

    /**
     * @var TriggerIndexHooks
     */
    private TriggerIndexHooks $middleware;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->queryParameters = QueryParameters::fromArray([
            'include' => 'author,tags',
        ]);
        $this->middleware = new TriggerIndexHooks();
    }

    /**
     * @return void
     */
    public function testItHasNoHooks(): void
    {
        $request = $this->createMock(Request::class);
        $query = FetchManyQuery::make($request, new QueryMany(new ResourceType('tags')));

        $expected = Result::ok(
            new Payload(null, true),
        );

        $actual = $this->middleware->handle(
            $query,
            function (FetchManyQuery $passed) use ($query, $expected): Result {
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
        $hooks = $this->createMock(IndexImplementation::class);
        $models = new ArrayIterator([]);
        $sequence = [];

        $query = FetchManyQuery::make($request, new QueryMany(new ResourceType('tags')))
            ->withValidated($this->queryParameters->toQuery())
            ->withHooks($hooks);

        $hooks
            ->expects($this->once())
            ->method('searching')
            ->willReturnCallback(function ($req, $q) use (&$sequence, $request): void {
                $sequence[] = 'searching';
                $this->assertSame($request, $req);
                $this->assertEquals($this->queryParameters, $q);
            });

        $hooks
            ->expects($this->once())
            ->method('searched')
            ->willReturnCallback(function ($m, $req, $q) use (&$sequence, $models, $request): void {
                $sequence[] = 'searched';
                $this->assertSame($m, $models);
                $this->assertSame($request, $req);
                $this->assertEquals($this->queryParameters, $q);
            });

        $expected = Result::ok(
            new Payload($models, true),
            $this->createMock(QueryParameters::class),
        );

        $actual = $this->middleware->handle(
            $query,
            function (FetchManyQuery $passed) use ($query, $expected, &$sequence): Result {
                $this->assertSame($query, $passed);
                $this->assertSame(['searching'], $sequence);
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
        $this->assertSame(['searching', 'searched'], $sequence);
    }

    /**
     * @return void
     */
    public function testItDoesNotTriggerSearchedHookOnFailure(): void
    {
        $request = $this->createMock(Request::class);
        $hooks = $this->createMock(IndexImplementation::class);
        $sequence = [];

        $query = FetchManyQuery::make($request, new QueryMany(new ResourceType('tags')))
            ->withValidated($this->queryParameters->toQuery())
            ->withHooks($hooks);

        $hooks
            ->expects($this->once())
            ->method('searching')
            ->willReturnCallback(function ($req, $q) use (&$sequence, $request): void {
                $sequence[] = 'searching';
                $this->assertSame($request, $req);
                $this->assertEquals($this->queryParameters, $q);
            });

        $hooks
            ->expects($this->never())
            ->method('searched');

        $expected = Result::failed();

        $actual = $this->middleware->handle(
            $query,
            function (FetchManyQuery $passed) use ($query, $expected, &$sequence): Result {
                $this->assertSame($query, $passed);
                $this->assertSame(['searching'], $sequence);
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
        $this->assertSame(['searching'], $sequence);
    }
}
