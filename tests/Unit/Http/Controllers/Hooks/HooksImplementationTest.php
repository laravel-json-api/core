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
use LaravelJsonApi\Contracts\Http\Controllers\Hooks\ShowRelatedImplementation;
use LaravelJsonApi\Contracts\Http\Controllers\Hooks\ShowRelationshipImplementation;
use LaravelJsonApi\Contracts\Http\Controllers\Hooks\StoreImplementation;
use LaravelJsonApi\Contracts\Http\Controllers\Hooks\UpdateImplementation;
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
            'updating' => [
                static function (HooksImplementation $impl, Request $request, QueryParameters $query): void {
                    $impl->updating(new stdClass(), $request, $query);
                },
            ],
            'updated' => [
                static function (HooksImplementation $impl, Request $request, QueryParameters $query): void {
                    $impl->updated(new stdClass(), $request, $query);
                },
            ],
            'readingRelated' => [
                static function (HooksImplementation $impl, Request $request, QueryParameters $query): void {
                    $impl->readingRelated(new stdClass(), 'comments', $request, $query);
                },
            ],
            'readRelated' => [
                static function (HooksImplementation $impl, Request $request, QueryParameters $query): void {
                    $impl->readRelated(new stdClass(), 'comments', [], $request, $query);
                },
            ],
            'readingRelationship' => [
                static function (HooksImplementation $impl, Request $request, QueryParameters $query): void {
                    $impl->readingRelationship(new stdClass(), 'comments', $request, $query);
                },
            ],
            'readRelationship' => [
                static function (HooksImplementation $impl, Request $request, QueryParameters $query): void {
                    $impl->readRelationship(new stdClass(), 'comments', [], $request, $query);
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
    public function testItInvokesReadingRelatedMethod(): void
    {
        $target = new class {
            public ?stdClass $model = null;
            public ?Request $request = null;
            public ?QueryParameters $query = null;

            public function readingRelatedBlogPosts(
                stdClass $model,
                Request $request,
                QueryParameters $query,
            ): void
            {
                $this->model = $model;
                $this->request = $request;
                $this->query = $query;
            }
        };

        $model = new stdClass();

        $implementation = new HooksImplementation($target);
        $implementation->readingRelated($model, 'blog-posts', $this->request, $this->query);

        $this->assertInstanceOf(ShowRelatedImplementation::class, $implementation);
        $this->assertSame($model, $target->model);
        $this->assertSame($this->request, $target->request);
        $this->assertSame($this->query, $target->query);
    }

    /**
     * @return void
     */
    public function testItInvokesReadingRelatedMethodAndThrowsResponse(): void
    {
        $response = $this->createMock(Response::class);

        $target = new class($response) {
            public ?stdClass $model = null;
            public ?Request $request = null;
            public ?QueryParameters $query = null;

            public function __construct(private readonly Response $response)
            {
            }

            public function readingRelatedComments(
                stdClass $model,
                Request $request,
                QueryParameters $query,
            ): Response
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
            $implementation->readingRelated($model, 'comments', $this->request, $this->query);
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
    public function testItInvokesReadingRelatedMethodAndThrowsResponseFromResponsable(): void
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

            public function readingRelatedTags(
                stdClass $model,
                Request $request,
                QueryParameters $query,
            ): Responsable
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
            $implementation->readingRelated($model, 'tags', $this->request, $this->query);
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
    public function testItInvokesReadRelatedMethod(): void
    {
        $target = new class {
            public ?stdClass $model = null;
            public ?ArrayObject $related = null;
            public ?Request $request = null;
            public ?QueryParameters $query = null;

            public function readRelatedBlogPosts(
                stdClass $model,
                ArrayObject $related,
                Request $request,
                QueryParameters $query,
            ): void
            {
                $this->model = $model;
                $this->related = $related;
                $this->request = $request;
                $this->query = $query;
            }
        };

        $model = new stdClass();
        $related = new ArrayObject();

        $implementation = new HooksImplementation($target);
        $implementation->readRelated($model, 'blog-posts', $related, $this->request, $this->query);

        $this->assertSame($model, $target->model);
        $this->assertSame($related, $target->related);
        $this->assertSame($this->request, $target->request);
        $this->assertSame($this->query, $target->query);
    }

    /**
     * @return void
     */
    public function testItInvokesReadRelatedMethodAndThrowsResponse(): void
    {
        $response = $this->createMock(Response::class);

        $target = new class($response) {
            public ?stdClass $model = null;
            public ?ArrayObject $related = null;
            public ?Request $request = null;
            public ?QueryParameters $query = null;

            public function __construct(private readonly Response $response)
            {
            }

            public function readRelatedComments(
                stdClass $model,
                ArrayObject $related,
                Request $request,
                QueryParameters $query,
            ): Response
            {
                $this->model = $model;
                $this->related = $related;
                $this->request = $request;
                $this->query = $query;

                return $this->response;
            }
        };

        $model = new stdClass();
        $related = new ArrayObject();
        $implementation = new HooksImplementation($target);

        try {
            $implementation->readRelated($model, 'comments', $related, $this->request, $this->query);
            $this->fail('No exception thrown.');
        } catch (HttpResponseException $ex) {
            $this->assertSame($model, $target->model);
            $this->assertSame($related, $target->related);
            $this->assertSame($this->request, $target->request);
            $this->assertSame($this->query, $target->query);
            $this->assertSame($response, $ex->getResponse());
        }
    }

    /**
     * @return void
     */
    public function testItInvokesReadRelatedMethodAndThrowsResponseFromResponsable(): void
    {
        $result = $this->createMock(Responsable::class);
        $result
            ->expects($this->once())
            ->method('toResponse')
            ->with($this->identicalTo($this->request))
            ->willReturn($response = $this->createMock(Response::class));

        $target = new class($result) {
            public ?stdClass $model = null;
            public ?ArrayObject $related = null;
            public ?Request $request = null;
            public ?QueryParameters $query = null;

            public function __construct(private readonly Responsable $result)
            {
            }

            public function readRelatedTags(
                stdClass $model,
                ArrayObject $related,
                Request $request,
                QueryParameters $query,
            ): Responsable
            {
                $this->model = $model;
                $this->related = $related;
                $this->request = $request;
                $this->query = $query;

                return $this->result;
            }
        };

        $model = new stdClass();
        $related = new ArrayObject();
        $implementation = new HooksImplementation($target);

        try {
            $implementation->readRelated($model, 'tags', $related, $this->request, $this->query);
            $this->fail('No exception thrown.');
        } catch (HttpResponseException $ex) {
            $this->assertSame($model, $target->model);
            $this->assertSame($related, $target->related);
            $this->assertSame($this->request, $target->request);
            $this->assertSame($this->query, $target->query);
            $this->assertSame($response, $ex->getResponse());
        }
    }

    /**
     * @return void
     */
    public function testItInvokesReadingRelationshipMethod(): void
    {
        $target = new class {
            public ?stdClass $model = null;
            public ?Request $request = null;
            public ?QueryParameters $query = null;

            public function readingBlogPosts(
                stdClass $model,
                Request $request,
                QueryParameters $query,
            ): void
            {
                $this->model = $model;
                $this->request = $request;
                $this->query = $query;
            }
        };

        $model = new stdClass();

        $implementation = new HooksImplementation($target);
        $implementation->readingRelationship($model, 'blog-posts', $this->request, $this->query);

        $this->assertInstanceOf(ShowRelationshipImplementation::class, $implementation);
        $this->assertSame($model, $target->model);
        $this->assertSame($this->request, $target->request);
        $this->assertSame($this->query, $target->query);
    }

    /**
     * @return void
     */
    public function testItInvokesReadingRelationshipMethodAndThrowsResponse(): void
    {
        $response = $this->createMock(Response::class);

        $target = new class($response) {
            public ?stdClass $model = null;
            public ?Request $request = null;
            public ?QueryParameters $query = null;

            public function __construct(private readonly Response $response)
            {
            }

            public function readingComments(
                stdClass $model,
                Request $request,
                QueryParameters $query,
            ): Response
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
            $implementation->readingRelationship($model, 'comments', $this->request, $this->query);
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
    public function testItInvokesReadingRelationshipMethodAndThrowsResponseFromResponsable(): void
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

            public function readingTags(
                stdClass $model,
                Request $request,
                QueryParameters $query,
            ): Responsable
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
            $implementation->readingRelationship($model, 'tags', $this->request, $this->query);
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
    public function testItInvokesReadRelationshipMethod(): void
    {
        $target = new class {
            public ?stdClass $model = null;
            public ?ArrayObject $related = null;
            public ?Request $request = null;
            public ?QueryParameters $query = null;

            public function readBlogPosts(
                stdClass $model,
                ArrayObject $related,
                Request $request,
                QueryParameters $query,
            ): void
            {
                $this->model = $model;
                $this->related = $related;
                $this->request = $request;
                $this->query = $query;
            }
        };

        $model = new stdClass();
        $related = new ArrayObject();

        $implementation = new HooksImplementation($target);
        $implementation->readRelationship($model, 'blog-posts', $related, $this->request, $this->query);

        $this->assertSame($model, $target->model);
        $this->assertSame($related, $target->related);
        $this->assertSame($this->request, $target->request);
        $this->assertSame($this->query, $target->query);
    }

    /**
     * @return void
     */
    public function testItInvokesReadRelationshipMethodAndThrowsResponse(): void
    {
        $response = $this->createMock(Response::class);

        $target = new class($response) {
            public ?stdClass $model = null;
            public ?ArrayObject $related = null;
            public ?Request $request = null;
            public ?QueryParameters $query = null;

            public function __construct(private readonly Response $response)
            {
            }

            public function readComments(
                stdClass $model,
                ArrayObject $related,
                Request $request,
                QueryParameters $query,
            ): Response
            {
                $this->model = $model;
                $this->related = $related;
                $this->request = $request;
                $this->query = $query;

                return $this->response;
            }
        };

        $model = new stdClass();
        $related = new ArrayObject();
        $implementation = new HooksImplementation($target);

        try {
            $implementation->readRelationship($model, 'comments', $related, $this->request, $this->query);
            $this->fail('No exception thrown.');
        } catch (HttpResponseException $ex) {
            $this->assertSame($model, $target->model);
            $this->assertSame($related, $target->related);
            $this->assertSame($this->request, $target->request);
            $this->assertSame($this->query, $target->query);
            $this->assertSame($response, $ex->getResponse());
        }
    }

    /**
     * @return void
     */
    public function testItInvokesReadRelationshipMethodAndThrowsResponseFromResponsable(): void
    {
        $result = $this->createMock(Responsable::class);
        $result
            ->expects($this->once())
            ->method('toResponse')
            ->with($this->identicalTo($this->request))
            ->willReturn($response = $this->createMock(Response::class));

        $target = new class($result) {
            public ?stdClass $model = null;
            public ?ArrayObject $related = null;
            public ?Request $request = null;
            public ?QueryParameters $query = null;

            public function __construct(private readonly Responsable $result)
            {
            }

            public function readTags(
                stdClass $model,
                ArrayObject $related,
                Request $request,
                QueryParameters $query,
            ): Responsable
            {
                $this->model = $model;
                $this->related = $related;
                $this->request = $request;
                $this->query = $query;

                return $this->result;
            }
        };

        $model = new stdClass();
        $related = new ArrayObject();
        $implementation = new HooksImplementation($target);

        try {
            $implementation->readRelationship($model, 'tags', $related, $this->request, $this->query);
            $this->fail('No exception thrown.');
        } catch (HttpResponseException $ex) {
            $this->assertSame($model, $target->model);
            $this->assertSame($related, $target->related);
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

    /**
     * @return void
     */
    public function testItInvokesUpdatingMethod(): void
    {
        $target = new class {
            public ?stdClass $model = null;
            public ?Request $request = null;
            public ?QueryParameters $query = null;

            public function updating(stdClass $model, Request $request, QueryParameters $query): void
            {
                $this->model = $model;
                $this->request = $request;
                $this->query = $query;
            }
        };

        $model = new stdClass();
        $implementation = new HooksImplementation($target);
        $implementation->updating($model, $this->request, $this->query);

        $this->assertInstanceOf(UpdateImplementation::class, $implementation);
        $this->assertSame($model, $target->model);
        $this->assertSame($this->request, $target->request);
        $this->assertSame($this->query, $target->query);
    }

    /**
     * @return void
     */
    public function testItInvokesUpdatingMethodAndThrowsResponse(): void
    {
        $response = $this->createMock(Response::class);

        $target = new class($response) {
            public ?stdClass $model = null;
            public ?Request $request = null;
            public ?QueryParameters $query = null;

            public function __construct(private readonly Response $response)
            {
            }

            public function updating(stdClass $model, Request $request, QueryParameters $query): Response
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
            $implementation->updating($model, $this->request, $this->query);
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
    public function testItInvokesUpdatingMethodAndThrowsResponseFromResponsable(): void
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

            public function updating(stdClass $model, Request $request, QueryParameters $query): Responsable
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
            $implementation->updating($model, $this->request, $this->query);
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
    public function testItInvokesUpdatedMethod(): void
    {
        $target = new class {
            public ?stdClass $model = null;
            public ?Request $request = null;
            public ?QueryParameters $query = null;

            public function updated(stdClass $model, Request $request, QueryParameters $query): void
            {
                $this->model = $model;
                $this->request = $request;
                $this->query = $query;
            }
        };

        $model = new stdClass();

        $implementation = new HooksImplementation($target);
        $implementation->updated($model, $this->request, $this->query);

        $this->assertSame($model, $target->model);
        $this->assertSame($this->request, $target->request);
        $this->assertSame($this->query, $target->query);
    }

    /**
     * @return void
     */
    public function testItInvokesUpdatedMethodAndThrowsResponse(): void
    {
        $response = $this->createMock(Response::class);

        $target = new class($response) {
            public ?stdClass $model = null;
            public ?Request $request = null;
            public ?QueryParameters $query = null;

            public function __construct(private readonly Response $response)
            {
            }

            public function updated(stdClass $model, Request $request, QueryParameters $query): Response
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
            $implementation->updated($model, $this->request, $this->query);
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
    public function testItInvokesUpdatedMethodAndThrowsResponseFromResponsable(): void
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

            public function updated(stdClass $model, Request $request, QueryParameters $query): Responsable
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
            $implementation->updated($model, $this->request, $this->query);
            $this->fail('No exception thrown.');
        } catch (HttpResponseException $ex) {
            $this->assertSame($model, $target->model);
            $this->assertSame($this->request, $target->request);
            $this->assertSame($this->query, $target->query);
            $this->assertSame($response, $ex->getResponse());
        }
    }
}
