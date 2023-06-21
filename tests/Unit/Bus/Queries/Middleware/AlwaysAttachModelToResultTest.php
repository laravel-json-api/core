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

use LaravelJsonApi\Contracts\Query\QueryParameters;
use LaravelJsonApi\Core\Bus\Queries\FetchOne\FetchOneQuery;
use LaravelJsonApi\Core\Bus\Queries\Middleware\AlwaysAttachModelToResult;
use LaravelJsonApi\Core\Bus\Queries\Result;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result as Payload;
use PHPUnit\Framework\TestCase;

class AlwaysAttachModelToResultTest extends TestCase
{
    /**
     * @var AlwaysAttachModelToResult
     */
    private AlwaysAttachModelToResult $middleware;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->middleware = new AlwaysAttachModelToResult();
    }

    /**
     * @return void
     */
    public function testItAttachesModel(): void
    {
        $result = Result::ok(
            $payload = new Payload(null, true),
            $queryParams = $this->createMock(QueryParameters::class),
        );

        $query = FetchOneQuery::make(null, 'posts')
            ->withModel($model = new \stdClass());

        $actual = $this->middleware->handle(
            $query,
            function (FetchOneQuery $passed) use ($query, $result): Result {
                $this->assertSame($query, $passed);
                return $result;
            },
        );

        $this->assertNotSame($result, $actual);
        $this->assertSame($payload, $actual->payload());
        $this->assertSame($queryParams, $actual->query());
        $this->assertSame($model, $actual->model());
    }

    /**
     * @return void
     */
    public function testItFailsIfNoModelIsSet(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Expecting a model to be set on the query.');

        $query = FetchOneQuery::make(null, 'posts');

        $this->middleware->handle(
            $query,
            fn () => $this->fail('Not expecting next middleware to be called.'),
        );
    }
}