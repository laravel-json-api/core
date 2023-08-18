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
                    return FetchOneQuery::make(null, 'posts', '123');
                },
            ],
            'fetch-related' => [
                static function (): FetchRelatedQuery {
                    return FetchRelatedQuery::make(null, 'posts', '123', 'comments');
                },
            ],
            'fetch-relationship' => [
                static function (): FetchRelationshipQuery {
                    return FetchRelationshipQuery::make(null, 'posts', '123', 'comments');
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
