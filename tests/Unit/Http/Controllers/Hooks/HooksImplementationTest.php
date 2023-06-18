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

namespace LaravelJsonApi\Core\Tests\Unit\Http\Controllers\Hooks;

use ArrayObject;
use Closure;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Http\Controllers\Hooks\IndexImplementation;
use LaravelJsonApi\Contracts\Http\Controllers\Hooks\ShowImplementation;
use LaravelJsonApi\Contracts\Http\Controllers\Hooks\StoreImplementation;
use LaravelJsonApi\Contracts\Query\QueryParameters;
use LaravelJsonApi\Core\Http\Controllers\Hooks\HooksImplementation;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\HttpFoundation\Response;

class HooksImplementationTest extends TestCase
{
    /**
     * @var MockObject&Request
     */
    private Request&MockObject $request;

    /**
     * @var MockObject&QueryParameters
     */
    private QueryParameters&MockObject $query;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->request = $this->createMock(Request::class);
        $this->query = $this->createMock(QueryParameters::class);
    }

    /**
     * @return array<string,array<Closure>>
     */
    public function withoutHooksProvider(): array
    {
        return [
            'searching' => [
                static function (HooksImplementation $impl, Request $request, QueryParameters $query): void {
                    $impl->searching($request, $query);
                },
            ],
            'searched' => [
                static function (HooksImplementation $impl, Request $request, QueryParameters $query): void {
                    $impl->searched([], $request, $query);
                },
            ],
            'reading' => [
                static function (HooksImplementation $impl, Request $request, QueryParameters $query): void {
                    $impl->reading($request, $query);
                },
            ],
            'read' => [
                static function (HooksImplementation $impl, Request $request, QueryParameters $query): void {
                    $impl->read(new stdClass(), $request, $query);
                },
            ],
            'saving' => [
                static function (HooksImplementation $impl, Request $request, QueryParameters $query): void {
                    $impl->saving(null, $request, $query);
                },
            ],
            'saved' => [
                static function (HooksImplementation $impl, Request $request, QueryParameters $query): void {
                    $impl->saved(new stdClass(), $request, $query);
                },
            ],
            'creating' => [
                static function (HooksImplementation $impl, Request $request, QueryParameters $query): void {
                    $impl->creating($request, $query);
                },
            ],
            'created' => [
                static function (HooksImplementation $impl, Request $request, QueryParameters $query): void {
                    $impl->created(new stdClass(), $request, $query);
                },
            ],
        ];
    }

    /**
     * @param Closure $scenario
     * @return void
     * @dataProvider withoutHooksProvider
     */
    public function testItDoesNotInvokeMissingHook(Closure $scenario): void
    {
        $implementation = new HooksImplementation(new class {});
        $scenario($implementation, $this->request, $this->query);
        $this->assertTrue(true);
    }

    /**
     * @return void
     */
    public function testItInvokesSearchingMethod(): void
    {
        $target = new class {
            public ?Request $request = null;
            public ?QueryParameters $query = null;

            public function searching(Request $request, QueryParameters $query): void
            {
                $this->request = $request;
                $this->query = $query;
            }
        };

        $implementation = new HooksImplementation($target);
        $implementation->searching($this->request, $this->query);

        $this->assertInstanceOf(IndexImplementation::class, $implementation);
        $this->assertSame($this->request, $target->request);
        $this->assertSame($this->query, $target->query);
    }

    /**
     * @return void
     */
    public function testItInvokesSearchingMethodAndThrowsResponse(): void
    {
        $response = $this->createMock(Response::class);

        $target = new class($response) {
            public ?Request $request = null;
            public ?QueryParameters $query = null;

            public function __construct(private readonly Response $response)
            {
            }

            public function searching(Request $request, QueryParameters $query): Response
            {
                $this->request = $request;
                $this->query = $query;

                return $this->response;
            }
        };

        $implementation = new HooksImplementation($target);

        try {
            $implementation->searching($this->request, $this->query);
            $this->fail('No exception thrown.');
        } catch (HttpResponseException $ex) {
            $this->assertSame($this->request, $target->request);
            $this->assertSame($this->query, $target->query);
            $this->assertSame($response, $ex->getResponse());
        }
    }

    /**
     * @return void
     */
    public function testItInvokesSearchingMethodAndThrowsResponseFromResponsable(): void
    {
        $result = $this->createMock(Responsable::class);
        $result
            ->expects($this->once())
            ->method('toResponse')
            ->with($this->identicalTo($this->request))
            ->willReturn($response = $this->createMock(Response::class));

        $target = new class($result) {
            public ?Request $request = null;
            public ?QueryParameters $query = null;

            public function __construct(private readonly Responsable $result)
            {
            }

            public function searching(Request $request, QueryParameters $query): Responsable
            {
                $this->request = $request;
                $this->query = $query;

                return $this->result;
            }
        };

        $implementation = new HooksImplementation($target);

        try {
            $implementation->searching($this->request, $this->query);
            $this->fail('No exception thrown.');
        } catch (HttpResponseException $ex) {
            $this->assertSame($this->request, $target->request);
            $this->assertSame($this->query, $target->query);
            $this->assertSame($response, $ex->getResponse());
        }
    }

    /**
     * @return void
     */
    public function testItInvokesSearchedMethod(): void
    {
        $models = new ArrayObject();

        $target = new class() {
            public mixed $models = null;
            public ?Request $request = null;
            public ?QueryParameters $query = null;

            public function searched(mixed $models, Request $request, QueryParameters $query): void
            {
                $this->models = $models;
                $this->request = $request;
                $this->query = $query;
            }
        };

        $implementation = new HooksImplementation($target);
        $implementation->searched($models, $this->request, $this->query);

        $this->assertSame($models, $target->models);
        $this->assertSame($this->request, $target->request);
        $this->assertSame($this->query, $target->query);
    }

    /**
     * @return void
     */
    public function testItInvokesSearchedMethodAndThrowsResponse(): void
    {
        $models = new ArrayObject();
        $response = $this->createMock(Response::class);

        $target = new class($response) {
            public mixed $models = null;
            public ?Request $request = null;
            public ?QueryParameters $query = null;

            public function __construct(private readonly Response $response)
            {
            }

            public function searched(mixed $models, Request $request, QueryParameters $query): Response
            {
                $this->models = $models;
                $this->request = $request;
                $this->query = $query;

                return $this->response;
            }
        };

        $implementation = new HooksImplementation($target);

        try {
            $implementation->searched($models, $this->request, $this->query);
            $this->fail('No exception thrown.');
        } catch (HttpResponseException $ex) {
            $this->assertSame($models, $target->models);
            $this->assertSame($this->request, $target->request);
            $this->assertSame($this->query, $target->query);
            $this->assertSame($response, $ex->getResponse());
        }
    }

    /**
     * @return void
     */
    public function testItInvokesSearchedMethodAndThrowsResponseFromResponsable(): void
    {
        $models = new ArrayObject();
        $result = $this->createMock(Responsable::class);
        $result
            ->expects($this->once())
            ->method('toResponse')
            ->with($this->identicalTo($this->request))
            ->willReturn($response = $this->createMock(Response::class));

        $target = new class($result) {
            public mixed $models = null;
            public ?Request $request = null;
            public ?QueryParameters $query = null;

            public function __construct(private readonly Responsable $result)
            {
            }

            public function searched(mixed $models, Request $request, QueryParameters $query): Responsable
            {
                $this->models = $models;
                $this->request = $request;
                $this->query = $query;

                return $this->result;
            }
        };

        $implementation = new HooksImplementation($target);

        try {
            $implementation->searched($models, $this->request, $this->query);
            $this->fail('No exception thrown.');
        } catch (HttpResponseException $ex) {
            $this->assertSame($models, $target->models);
            $this->assertSame($this->request, $target->request);
            $this->assertSame($this->query, $target->query);
            $this->assertSame($response, $ex->getResponse());
        }
    }

    /**
     * @return void
     */
    public function testItInvokesReadingMethod(): void
    {
        $target = new class {
            public ?Request $request = null;
            public ?QueryParameters $query = null;

            public function reading(Request $request, QueryParameters $query): void
            {
                $this->request = $request;
                $this->query = $query;
            }
        };

        $implementation = new HooksImplementation($target);
        $implementation->reading($this->request, $this->query);

        $this->assertInstanceOf(ShowImplementation::class, $implementation);
        $this->assertSame($this->request, $target->request);
        $this->assertSame($this->query, $target->query);
    }

    /**
     * @return void
     */
    public function testItInvokesReadingMethodAndThrowsResponse(): void
    {
        $response = $this->createMock(Response::class);

        $target = new class($response) {
            public ?Request $request = null;
            public ?QueryParameters $query = null;

            public function __construct(private readonly Response $response)
            {
            }

            public function reading(Request $request, QueryParameters $query): Response
            {
                $this->request = $request;
                $this->query = $query;

                return $this->response;
            }
        };

        $implementation = new HooksImplementation($target);

        try {
            $implementation->reading($this->request, $this->query);
            $this->fail('No exception thrown.');
        } catch (HttpResponseException $ex) {
            $this->assertSame($this->request, $target->request);
            $this->assertSame($this->query, $target->query);
            $this->assertSame($response, $ex->getResponse());
        }
    }

    /**
     * @return void
     */
    public function testItInvokesReadingMethodAndThrowsResponseFromResponsable(): void
    {
        $result = $this->createMock(Responsable::class);
        $result
            ->expects($this->once())
            ->method('toResponse')
            ->with($this->identicalTo($this->request))
            ->willReturn($response = $this->createMock(Response::class));

        $target = new class($result) {
            public ?Request $request = null;
            public ?QueryParameters $query = null;

            public function __construct(private readonly Responsable $result)
            {
            }

            public function reading(Request $request, QueryParameters $query): Responsable
            {
                $this->request = $request;
                $this->query = $query;

                return $this->result;
            }
        };

        $implementation = new HooksImplementation($target);

        try {
            $implementation->reading($this->request, $this->query);
            $this->fail('No exception thrown.');
        } catch (HttpResponseException $ex) {
            $this->assertSame($this->request, $target->request);
            $this->assertSame($this->query, $target->query);
            $this->assertSame($response, $ex->getResponse());
        }
    }

    /**
     * @return void
     */
    public function testItInvokesReadMethod(): void
    {
        $target = new class {
            public ?stdClass $model = null;
            public ?Request $request = null;
            public ?QueryParameters $query = null;

            public function read(stdClass $model, Request $request, QueryParameters $query): void
            {
                $this->model = $model;
                $this->request = $request;
                $this->query = $query;
            }
        };

        $model = new stdClass();

        $implementation = new HooksImplementation($target);
        $implementation->read($model, $this->request, $this->query);

        $this->assertInstanceOf(ShowImplementation::class, $implementation);
        $this->assertSame($model, $target->model);
        $this->assertSame($this->request, $target->request);
        $this->assertSame($this->query, $target->query);
    }

    /**
     * @return void
     */
    public function testItInvokesReadMethodAndThrowsResponse(): void
    {
        $response = $this->createMock(Response::class);

        $target = new class($response) {
            public ?stdClass $model = null;
            public ?Request $request = null;
            public ?QueryParameters $query = null;

            public function __construct(private readonly Response $response)
            {
            }

            public function read(stdClass $model, Request $request, QueryParameters $query): Response
            {
                $this->model = $model;
                $this->request = $request;
                $this->query = $query;

                return $this->response;
            }
        };

        $model = new stdClass();
        $implementation = new HooksImplementation($target);

        try {
            $implementation->read($model, $this->request, $this->query);
            $this->fail('No exception thrown.');
        } catch (HttpResponseException $ex) {
            $this->assertSame($model, $target->model);
            $this->assertSame($this->request, $target->request);
            $this->assertSame($this->query, $target->query);
            $this->assertSame($response, $ex->getResponse());
        }
    }

    /**
     * @return void
     */
    public function testItInvokesReadMethodAndThrowsResponseFromResponsable(): void
    {
        $result = $this->createMock(Responsable::class);
        $result
            ->expects($this->once())
            ->method('toResponse')
            ->with($this->identicalTo($this->request))
            ->willReturn($response = $this->createMock(Response::class));

        $target = new class($result) {
            public ?stdClass $model = null;
            public ?Request $request = null;
            public ?QueryParameters $query = null;

            public function __construct(private readonly Responsable $result)
            {
            }

            public function read(stdClass $model, Request $request, QueryParameters $query): Responsable
            {
                $this->model = $model;
                $this->request = $request;
                $this->query = $query;

                return $this->result;
            }
        };

        $model = new stdClass();
        $implementation = new HooksImplementation($target);

        try {
            $implementation->read($model, $this->request, $this->query);
            $this->fail('No exception thrown.');
        } catch (HttpResponseException $ex) {
            $this->assertSame($model, $target->model);
            $this->assertSame($this->request, $target->request);
            $this->assertSame($this->query, $target->query);
            $this->assertSame($response, $ex->getResponse());
        }
    }

    /**
     * @return void
     */
    public function testItInvokesSavingMethod(): void
    {
        $target = new class {
            public ?stdClass $model = null;
            public ?Request $request = null;
            public ?QueryParameters $query = null;

            public function saving(stdClass $model, Request $request, QueryParameters $query): void
            {
                $this->model = $model;
                $this->request = $request;
                $this->query = $query;
            }
        };

        $model = new stdClass();

        $implementation = new HooksImplementation($target);
        $implementation->saving($model, $this->request, $this->query);

        $this->assertSame($model, $target->model);
        $this->assertSame($this->request, $target->request);
        $this->assertSame($this->query, $target->query);
    }

    /**
     * @return void
     */
    public function testItInvokesSavingMethodAndThrowsResponse(): void
    {
        $response = $this->createMock(Response::class);

        $target = new class($response) {
            public ?bool $model = true;
            public ?Request $request = null;
            public ?QueryParameters $query = null;

            public function __construct(private readonly Response $response)
            {
            }

            public function saving(mixed $model, Request $request, QueryParameters $query): Response
            {
                $this->model = $model;
                $this->request = $request;
                $this->query = $query;

                return $this->response;
            }
        };

        $implementation = new HooksImplementation($target);

        try {
            $implementation->saving(null, $this->request, $this->query);
            $this->fail('No exception thrown.');
        } catch (HttpResponseException $ex) {
            $this->assertNull($target->model);
            $this->assertSame($this->request, $target->request);
            $this->assertSame($this->query, $target->query);
            $this->assertSame($response, $ex->getResponse());
        }
    }

    /**
     * @return void
     */
    public function testItInvokesSavingMethodAndThrowsResponseFromResponsable(): void
    {
        $result = $this->createMock(Responsable::class);
        $result
            ->expects($this->once())
            ->method('toResponse')
            ->with($this->identicalTo($this->request))
            ->willReturn($response = $this->createMock(Response::class));

        $target = new class($result) {
            public ?stdClass $model = null;
            public ?Request $request = null;
            public ?QueryParameters $query = null;

            public function __construct(private readonly Responsable $result)
            {
            }

            public function saving(stdClass $model, Request $request, QueryParameters $query): Responsable
            {
                $this->model = $model;
                $this->request = $request;
                $this->query = $query;

                return $this->result;
            }
        };

        $model = new stdClass();
        $implementation = new HooksImplementation($target);

        try {
            $implementation->saving($model, $this->request, $this->query);
            $this->fail('No exception thrown.');
        } catch (HttpResponseException $ex) {
            $this->assertSame($model, $target->model);
            $this->assertSame($this->request, $target->request);
            $this->assertSame($this->query, $target->query);
            $this->assertSame($response, $ex->getResponse());
        }
    }

    /**
     * @return void
     */
    public function testItInvokesSavedMethod(): void
    {
        $target = new class {
            public ?stdClass $model = null;
            public ?Request $request = null;
            public ?QueryParameters $query = null;

            public function saved(stdClass $model, Request $request, QueryParameters $query): void
            {
                $this->model = $model;
                $this->request = $request;
                $this->query = $query;
            }
        };

        $model = new stdClass();

        $implementation = new HooksImplementation($target);
        $implementation->saved($model, $this->request, $this->query);

        $this->assertSame($model, $target->model);
        $this->assertSame($this->request, $target->request);
        $this->assertSame($this->query, $target->query);
    }

    /**
     * @return void
     */
    public function testItInvokesSavedMethodAndThrowsResponse(): void
    {
        $response = $this->createMock(Response::class);

        $target = new class($response) {
            public ?stdClass $model = null;
            public ?Request $request = null;
            public ?QueryParameters $query = null;

            public function __construct(private readonly Response $response)
            {
            }

            public function saved(stdClass $model, Request $request, QueryParameters $query): Response
            {
                $this->model = $model;
                $this->request = $request;
                $this->query = $query;

                return $this->response;
            }
        };

        $model = new stdClass();
        $implementation = new HooksImplementation($target);

        try {
            $implementation->saved($model, $this->request, $this->query);
            $this->fail('No exception thrown.');
        } catch (HttpResponseException $ex) {
            $this->assertSame($model, $target->model);
            $this->assertSame($this->request, $target->request);
            $this->assertSame($this->query, $target->query);
            $this->assertSame($response, $ex->getResponse());
        }
    }

    /**
     * @return void
     */
    public function testItInvokesSavedMethodAndThrowsResponseFromResponsable(): void
    {
        $result = $this->createMock(Responsable::class);
        $result
            ->expects($this->once())
            ->method('toResponse')
            ->with($this->identicalTo($this->request))
            ->willReturn($response = $this->createMock(Response::class));

        $target = new class($result) {
            public ?stdClass $model = null;
            public ?Request $request = null;
            public ?QueryParameters $query = null;

            public function __construct(private readonly Responsable $result)
            {
            }

            public function saved(stdClass $model, Request $request, QueryParameters $query): Responsable
            {
                $this->model = $model;
                $this->request = $request;
                $this->query = $query;

                return $this->result;
            }
        };

        $model = new stdClass();
        $implementation = new HooksImplementation($target);

        try {
            $implementation->saved($model, $this->request, $this->query);
            $this->fail('No exception thrown.');
        } catch (HttpResponseException $ex) {
            $this->assertSame($model, $target->model);
            $this->assertSame($this->request, $target->request);
            $this->assertSame($this->query, $target->query);
            $this->assertSame($response, $ex->getResponse());
        }
    }

    /**
     * @return void
     */
    public function testItInvokesCreatingMethod(): void
    {
        $target = new class {
            public ?Request $request = null;
            public ?QueryParameters $query = null;

            public function creating(Request $request, QueryParameters $query): void
            {
                $this->request = $request;
                $this->query = $query;
            }
        };

        $implementation = new HooksImplementation($target);
        $implementation->creating($this->request, $this->query);

        $this->assertInstanceOf(StoreImplementation::class, $implementation);
        $this->assertSame($this->request, $target->request);
        $this->assertSame($this->query, $target->query);
    }

    /**
     * @return void
     */
    public function testItInvokesCreatingMethodAndThrowsResponse(): void
    {
        $response = $this->createMock(Response::class);

        $target = new class($response) {
            public ?Request $request = null;
            public ?QueryParameters $query = null;

            public function __construct(private readonly Response $response)
            {
            }

            public function creating(Request $request, QueryParameters $query): Response
            {
                $this->request = $request;
                $this->query = $query;

                return $this->response;
            }
        };

        $implementation = new HooksImplementation($target);

        try {
            $implementation->creating($this->request, $this->query);
            $this->fail('No exception thrown.');
        } catch (HttpResponseException $ex) {
            $this->assertSame($this->request, $target->request);
            $this->assertSame($this->query, $target->query);
            $this->assertSame($response, $ex->getResponse());
        }
    }

    /**
     * @return void
     */
    public function testItInvokesCreatingMethodAndThrowsResponseFromResponsable(): void
    {
        $result = $this->createMock(Responsable::class);
        $result
            ->expects($this->once())
            ->method('toResponse')
            ->with($this->identicalTo($this->request))
            ->willReturn($response = $this->createMock(Response::class));

        $target = new class($result) {
            public ?Request $request = null;
            public ?QueryParameters $query = null;

            public function __construct(private readonly Responsable $result)
            {
            }

            public function creating(Request $request, QueryParameters $query): Responsable
            {
                $this->request = $request;
                $this->query = $query;

                return $this->result;
            }
        };

        $implementation = new HooksImplementation($target);

        try {
            $implementation->creating($this->request, $this->query);
            $this->fail('No exception thrown.');
        } catch (HttpResponseException $ex) {
            $this->assertSame($this->request, $target->request);
            $this->assertSame($this->query, $target->query);
            $this->assertSame($response, $ex->getResponse());
        }
    }

    /**
     * @return void
     */
    public function testItInvokesCreatedMethod(): void
    {
        $target = new class {
            public ?stdClass $model = null;
            public ?Request $request = null;
            public ?QueryParameters $query = null;

            public function created(stdClass $model, Request $request, QueryParameters $query): void
            {
                $this->model = $model;
                $this->request = $request;
                $this->query = $query;
            }
        };

        $model = new stdClass();

        $implementation = new HooksImplementation($target);
        $implementation->created($model, $this->request, $this->query);

        $this->assertSame($model, $target->model);
        $this->assertSame($this->request, $target->request);
        $this->assertSame($this->query, $target->query);
    }

    /**
     * @return void
     */
    public function testItInvokesCreatedMethodAndThrowsResponse(): void
    {
        $response = $this->createMock(Response::class);

        $target = new class($response) {
            public ?stdClass $model = null;
            public ?Request $request = null;
            public ?QueryParameters $query = null;

            public function __construct(private readonly Response $response)
            {
            }

            public function created(stdClass $model, Request $request, QueryParameters $query): Response
            {
                $this->model = $model;
                $this->request = $request;
                $this->query = $query;

                return $this->response;
            }
        };

        $model = new stdClass();
        $implementation = new HooksImplementation($target);

        try {
            $implementation->created($model, $this->request, $this->query);
            $this->fail('No exception thrown.');
        } catch (HttpResponseException $ex) {
            $this->assertSame($model, $target->model);
            $this->assertSame($this->request, $target->request);
            $this->assertSame($this->query, $target->query);
            $this->assertSame($response, $ex->getResponse());
        }
    }

    /**
     * @return void
     */
    public function testItInvokesCreatedMethodAndThrowsResponseFromResponsable(): void
    {
        $result = $this->createMock(Responsable::class);
        $result
            ->expects($this->once())
            ->method('toResponse')
            ->with($this->identicalTo($this->request))
            ->willReturn($response = $this->createMock(Response::class));

        $target = new class($result) {
            public ?stdClass $model = null;
            public ?Request $request = null;
            public ?QueryParameters $query = null;

            public function __construct(private readonly Responsable $result)
            {
            }

            public function created(stdClass $model, Request $request, QueryParameters $query): Responsable
            {
                $this->model = $model;
                $this->request = $request;
                $this->query = $query;

                return $this->result;
            }
        };

        $model = new stdClass();
        $implementation = new HooksImplementation($target);

        try {
            $implementation->created($model, $this->request, $this->query);
            $this->fail('No exception thrown.');
        } catch (HttpResponseException $ex) {
            $this->assertSame($model, $target->model);
            $this->assertSame($this->request, $target->request);
            $this->assertSame($this->query, $target->query);
            $this->assertSame($response, $ex->getResponse());
        }
    }
}
