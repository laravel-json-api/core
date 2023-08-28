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

namespace LaravelJsonApi\Core\Tests\Unit\Bus\Queries\FetchRelated\Middleware;

use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Http\Hooks\ShowRelatedImplementation;
use LaravelJsonApi\Core\Bus\Queries\FetchRelated\FetchRelatedQuery;
use LaravelJsonApi\Core\Bus\Queries\FetchRelated\Middleware\TriggerShowRelatedHooks;
use LaravelJsonApi\Core\Bus\Queries\Result;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result as Payload;
use LaravelJsonApi\Core\Query\Input\QueryRelated;
use LaravelJsonApi\Core\Query\QueryParameters;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\TestCase;

class TriggerShowRelatedHooksTest extends TestCase
{
    /**
     * @var QueryParameters
     */
    private QueryParameters $queryParameters;

    /**
     * @var TriggerShowRelatedHooks
     */
    private TriggerShowRelatedHooks $middleware;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->queryParameters = QueryParameters::fromArray([
            'include' => 'author,tags',
        ]);
        $this->middleware = new TriggerShowRelatedHooks();
    }

    /**
     * @return void
     */
    public function testItHasNoHooks(): void
    {
        $request = $this->createMock(Request::class);
        $input = new QueryRelated(new ResourceType('tags'), new ResourceId('456'), 'videos');
        $query = FetchRelatedQuery::make($request, $input);

        $expected = Result::ok(
            new Payload(null, true),
        );

        $actual = $this->middleware->handle(
            $query,
            function (FetchRelatedQuery $passed) use ($query, $expected): Result {
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
        $hooks = $this->createMock(ShowRelatedImplementation::class);
        $model = new \stdClass();
        $related = new \ArrayObject();
        $sequence = [];

        $input = new QueryRelated(new ResourceType('posts'), new ResourceId('123'), 'tags');
        $query = FetchRelatedQuery::make($request, $input)
            ->withModel($model)
            ->withValidated($this->queryParameters->toQuery())
            ->withHooks($hooks);

        $hooks
            ->expects($this->once())
            ->method('readingRelated')
            ->willReturnCallback(function ($m, $f, $req, $q) use (&$sequence, $model, $request): void {
                $sequence[] = 'reading';
                $this->assertSame($model, $m);
                $this->assertSame('tags', $f);
                $this->assertSame($request, $req);
                $this->assertEquals($this->queryParameters, $q);
            });

        $hooks
            ->expects($this->once())
            ->method('readRelated')
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
            function (FetchRelatedQuery $passed) use ($query, $expected, &$sequence): Result {
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
        $hooks = $this->createMock(ShowRelatedImplementation::class);
        $sequence = [];

        $input = new QueryRelated(new ResourceType('tags'), new ResourceId('123'), 'createdBy');
        $query = FetchRelatedQuery::make($request, $input)
            ->withModel($model = new \stdClass())
            ->withValidated($this->queryParameters->toQuery())
            ->withHooks($hooks);

        $hooks
            ->expects($this->once())
            ->method('readingRelated')
            ->willReturnCallback(function ($m, $f, $req, $q) use (&$sequence, $model, $request): void {
                $sequence[] = 'reading';
                $this->assertSame($model, $m);
                $this->assertSame('createdBy', $f);
                $this->assertSame($request, $req);
                $this->assertEquals($this->queryParameters, $q);
            });

        $hooks
            ->expects($this->never())
            ->method('readRelated');

        $expected = Result::failed();

        $actual = $this->middleware->handle(
            $query,
            function (FetchRelatedQuery $passed) use ($query, $expected, &$sequence): Result {
                $this->assertSame($query, $passed);
                $this->assertSame(['reading'], $sequence);
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
        $this->assertSame(['reading'], $sequence);
    }
}
