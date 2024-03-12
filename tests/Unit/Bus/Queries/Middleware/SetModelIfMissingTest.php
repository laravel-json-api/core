<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Bus\Queries\Middleware;

use Closure;
use LaravelJsonApi\Contracts\Store\Store;
use LaravelJsonApi\Core\Bus\Queries\FetchOne\FetchOneQuery;
use LaravelJsonApi\Core\Bus\Queries\FetchRelated\FetchRelatedQuery;
use LaravelJsonApi\Core\Bus\Queries\FetchRelationship\FetchRelationshipQuery;
use LaravelJsonApi\Core\Bus\Queries\Middleware\SetModelIfMissing;
use LaravelJsonApi\Core\Bus\Queries\Query\IsIdentifiable;
use LaravelJsonApi\Core\Bus\Queries\Query\Query;
use LaravelJsonApi\Core\Bus\Queries\Result;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result as Payload;
use LaravelJsonApi\Core\Query\Input\QueryOne;
use LaravelJsonApi\Core\Query\Input\QueryRelated;
use LaravelJsonApi\Core\Query\Input\QueryRelationship;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;

class SetModelIfMissingTest extends TestCase
{
    /**
     * @var MockObject&Store
     */
    private Store&MockObject $store;

    /**
     * @var SetModelIfMissing
     */
    private SetModelIfMissing $middleware;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = new SetModelIfMissing(
            $this->store = $this->createMock(Store::class),
        );
    }

    /**
     * @return array<array<Closure>>
     */
    public static function modelRequiredProvider(): array
    {
        return [
            'fetch-one' => [
                static function (): FetchOneQuery {
                    return FetchOneQuery::make(null, new QueryOne(
                        new ResourceType('posts'),
                        new ResourceId('123'),
                    ));
                },
            ],
            'fetch-related' => [
                static function (): FetchRelatedQuery {
                    return FetchRelatedQuery::make(null, new QueryRelated(
                        new ResourceType('posts'),
                        new ResourceId('123'),
                        'comments',
                    ));
                },
            ],
            'fetch-relationship' => [
                static function (): FetchRelationshipQuery {
                    return FetchRelationshipQuery::make(null, new QueryRelationship(
                        new ResourceType('posts'),
                        new ResourceId('123'),
                        'comments',
                    ));
                },
            ],
        ];
    }

    /**
     * @param Closure<Query&IsIdentifiable> $scenario
     * @return void
     * @dataProvider modelRequiredProvider
     */
    public function testItFindsModel(Closure $scenario): void
    {
        $query = $scenario();
        $type = $query->type();
        $id = $query->id();

        $this->store
            ->expects($this->once())
            ->method('find')
            ->with($this->identicalTo($type), $this->identicalTo($id))
            ->willReturn($model = new stdClass());

        $expected = Result::ok(new Payload(null, true));

        $actual = $this->middleware->handle(
            $query,
            function (Query&IsIdentifiable $passed) use ($query, $model, $expected): Result {
                $this->assertNotSame($passed, $query);
                $this->assertSame($model, $passed->model());
                $this->assertSame($model, $passed->model());
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
    }

    /**
     * @param Closure<Query&IsIdentifiable> $scenario
     * @return void
     * @dataProvider modelRequiredProvider
     */
    public function testItDoesNotFindModelIfAlreadySet(Closure $scenario): void
    {
        $query = $scenario();
        $query = $query->withModel($model = new \stdClass());

        $this->store
            ->expects($this->never())
            ->method($this->anything());

        $expected = Result::ok(new Payload(null, true));

        $actual = $this->middleware->handle(
            $query,
            function (Query $passed) use ($query, $model, $expected): Result {
                $this->assertSame($passed, $query);
                $this->assertSame($model, $query->model());
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
    }
}
