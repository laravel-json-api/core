<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Bus\Queries\FetchRelationship\Middleware;

use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Http\Hooks\ShowRelationshipImplementation;
use LaravelJsonApi\Core\Bus\Queries\FetchRelationship\FetchRelationshipQuery;
use LaravelJsonApi\Core\Bus\Queries\FetchRelationship\Middleware\TriggerShowRelationshipHooks;
use LaravelJsonApi\Core\Bus\Queries\Result;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result as Payload;
use LaravelJsonApi\Core\Query\Input\QueryRelationship;
use LaravelJsonApi\Core\Query\QueryParameters;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\TestCase;

class TriggerShowRelationshipHooksTest extends TestCase
{
    /**
     * @var QueryParameters
     */
    private QueryParameters $queryParameters;

    /**
     * @var TriggerShowRelationshipHooks
     */
    private TriggerShowRelationshipHooks $middleware;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->queryParameters = QueryParameters::fromArray([
            'include' => 'author,tags',
        ]);
        $this->middleware = new TriggerShowRelationshipHooks();
    }

    /**
     * @return void
     */
    public function testItHasNoHooks(): void
    {
        $request = $this->createMock(Request::class);
        $input = new QueryRelationship(new ResourceType('tags'), new ResourceId('123'), 'videos');
        $query = FetchRelationshipQuery::make($request, $input);

        $expected = Result::ok(
            new Payload(null, true),
        );

        $actual = $this->middleware->handle(
            $query,
            function (FetchRelationshipQuery $passed) use ($query, $expected): Result {
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
        $hooks = $this->createMock(ShowRelationshipImplementation::class);
        $model = new \stdClass();
        $related = new \ArrayObject();
        $sequence = [];

        $input = new QueryRelationship(new ResourceType('posts'), new ResourceId('123'), 'tags');
        $query = FetchRelationshipQuery::make($request, $input)
            ->withModel($model)
            ->withValidated($this->queryParameters->toQuery())
            ->withHooks($hooks);

        $hooks
            ->expects($this->once())
            ->method('readingRelationship')
            ->willReturnCallback(function ($m, $f, $req, $q) use (&$sequence, $model, $request): void {
                $sequence[] = 'reading';
                $this->assertSame($model, $m);
                $this->assertSame('tags', $f);
                $this->assertSame($request, $req);
                $this->assertEquals($this->queryParameters, $q);
            });

        $hooks
            ->expects($this->once())
            ->method('readRelationship')
            ->willReturnCallback(function ($m, $f, $rel, $req, $q) use (&$sequence, $model, $related, $request): void {
                $sequence[] = 'read';
                $this->assertSame($model, $m);
                $this->assertSame('tags', $f);
                $this->assertSame($related, $rel);
                $this->assertSame($request, $req);
                $this->assertEquals($this->queryParameters, $q);
            });

        $expected = Result::ok(
            new Payload($related, true),
            $this->createMock(QueryParameters::class),
        );

        $actual = $this->middleware->handle(
            $query,
            function (FetchRelationshipQuery $passed) use ($query, $expected, &$sequence): Result {
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
        $hooks = $this->createMock(ShowRelationshipImplementation::class);
        $sequence = [];

        $input = new QueryRelationship(new ResourceType('tags'), new ResourceId('123'), 'createdBy');
        $query = FetchRelationshipQuery::make($request, $input)
            ->withModel($model = new \stdClass())
            ->withValidated($this->queryParameters->toQuery())
            ->withHooks($hooks);

        $hooks
            ->expects($this->once())
            ->method('readingRelationship')
            ->willReturnCallback(function ($m, $f, $req, $q) use (&$sequence, $model, $request): void {
                $sequence[] = 'reading';
                $this->assertSame($model, $m);
                $this->assertSame('createdBy', $f);
                $this->assertSame($request, $req);
                $this->assertEquals($this->queryParameters, $q);
            });

        $hooks
            ->expects($this->never())
            ->method('readRelationship');

        $expected = Result::failed();

        $actual = $this->middleware->handle(
            $query,
            function (FetchRelationshipQuery $passed) use ($query, $expected, &$sequence): Result {
                $this->assertSame($query, $passed);
                $this->assertSame(['reading'], $sequence);
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
        $this->assertSame(['reading'], $sequence);
    }
}
