<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Http\Hooks;

use ArrayObject;
use Closure;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Http\Hooks\AttachRelationshipImplementation;
use LaravelJsonApi\Contracts\Http\Hooks\DestroyImplementation;
use LaravelJsonApi\Contracts\Http\Hooks\DetachRelationshipImplementation;
use LaravelJsonApi\Contracts\Http\Hooks\IndexImplementation;
use LaravelJsonApi\Contracts\Http\Hooks\ShowImplementation;
use LaravelJsonApi\Contracts\Http\Hooks\ShowRelatedImplementation;
use LaravelJsonApi\Contracts\Http\Hooks\ShowRelationshipImplementation;
use LaravelJsonApi\Contracts\Http\Hooks\StoreImplementation;
use LaravelJsonApi\Contracts\Http\Hooks\UpdateImplementation;
use LaravelJsonApi\Contracts\Http\Hooks\UpdateRelationshipImplementation;
use LaravelJsonApi\Contracts\Query\QueryParameters;
use LaravelJsonApi\Core\Http\Hooks\HooksImplementation;
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
    public static function withoutHooksProvider(): array
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
            'deleting' => [
                static function (HooksImplementation $impl, Request $request): void {
                    $impl->deleting(new stdClass(), $request);
                },
            ],
            'deleted' => [
                static function (HooksImplementation $impl, Request $request): void {
                    $impl->deleted(new stdClass(), $request);
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
            'updatingRelationship' => [
                static function (HooksImplementation $impl, Request $request, QueryParameters $query): void {
                    $impl->updatingRelationship(new stdClass(), 'comments', $request, $query);
                },
            ],
            'updatedRelationship' => [
                static function (HooksImplementation $impl, Request $request, QueryParameters $query): void {
                    $impl->updatedRelationship(new stdClass(), 'comments', [], $request, $query);
                },
            ],
            'attachingRelationship' => [
                static function (HooksImplementation $impl, Request $request, QueryParameters $query): void {
                    $impl->attachingRelationship(new stdClass(), 'comments', $request, $query);
                },
            ],
            'attachedRelationship' => [
                static function (HooksImplementation $impl, Request $request, QueryParameters $query): void {
                    $impl->attachedRelationship(new stdClass(), 'comments', [], $request, $query);
                },
            ],
            'detachingRelationship' => [
                static function (HooksImplementation $impl, Request $request, QueryParameters $query): void {
                    $impl->detachingRelationship(new stdClass(), 'comments', $request, $query);
                },
            ],
            'detachedRelationship' => [
                static function (HooksImplementation $impl, Request $request, QueryParameters $query): void {
                    $impl->detachedRelationship(new stdClass(), 'comments', [], $request, $query);
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

    /**
     * @return void
     */
    public function testItInvokesDeletingMethod(): void
    {
        $target = new class {
            public ?stdClass $model = null;
            public ?Request $request = null;

            public function deleting(stdClass $model, Request $request): void
            {
                $this->model = $model;
                $this->request = $request;
            }
        };

        $model = new stdClass();
        $implementation = new HooksImplementation($target);
        $implementation->deleting($model, $this->request);

        $this->assertInstanceOf(DestroyImplementation::class, $implementation);
        $this->assertSame($model, $target->model);
        $this->assertSame($this->request, $target->request);
    }

    /**
     * @return void
     */
    public function testItInvokesDeletingMethodAndThrowsResponse(): void
    {
        $response = $this->createMock(Response::class);

        $target = new class($response) {
            public ?stdClass $model = null;
            public ?Request $request = null;

            public function __construct(private readonly Response $response)
            {
            }

            public function deleting(stdClass $model, Request $request): Response
            {
                $this->model = $model;
                $this->request = $request;

                return $this->response;
            }
        };

        $model = new stdClass();
        $implementation = new HooksImplementation($target);

        try {
            $implementation->deleting($model, $this->request);
            $this->fail('No exception thrown.');
        } catch (HttpResponseException $ex) {
            $this->assertSame($model, $target->model);
            $this->assertSame($this->request, $target->request);
            $this->assertSame($response, $ex->getResponse());
        }
    }

    /**
     * @return void
     */
    public function testItInvokesDeletingMethodAndThrowsResponseFromResponsable(): void
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

            public function __construct(private readonly Responsable $result)
            {
            }

            public function deleting(stdClass $model, Request $request): Responsable
            {
                $this->model = $model;
                $this->request = $request;

                return $this->result;
            }
        };

        $model = new stdClass();
        $implementation = new HooksImplementation($target);

        try {
            $implementation->deleting($model, $this->request);
            $this->fail('No exception thrown.');
        } catch (HttpResponseException $ex) {
            $this->assertSame($model, $target->model);
            $this->assertSame($this->request, $target->request);
            $this->assertSame($response, $ex->getResponse());
        }
    }

    /**
     * @return void
     */
    public function testItInvokesDeletedMethod(): void
    {
        $target = new class {
            public ?stdClass $model = null;
            public ?Request $request = null;

            public function deleted(stdClass $model, Request $request): void
            {
                $this->model = $model;
                $this->request = $request;
            }
        };

        $model = new stdClass();

        $implementation = new HooksImplementation($target);
        $implementation->deleted($model, $this->request, $this->query);

        $this->assertSame($model, $target->model);
        $this->assertSame($this->request, $target->request);
    }

    /**
     * @return void
     */
    public function testItInvokesDeletedMethodAndThrowsResponse(): void
    {
        $response = $this->createMock(Response::class);

        $target = new class($response) {
            public ?stdClass $model = null;
            public ?Request $request = null;

            public function __construct(private readonly Response $response)
            {
            }

            public function deleted(stdClass $model, Request $request): Response
            {
                $this->model = $model;
                $this->request = $request;

                return $this->response;
            }
        };

        $model = new stdClass();
        $implementation = new HooksImplementation($target);

        try {
            $implementation->deleted($model, $this->request, $this->query);
            $this->fail('No exception thrown.');
        } catch (HttpResponseException $ex) {
            $this->assertSame($model, $target->model);
            $this->assertSame($this->request, $target->request);
            $this->assertSame($response, $ex->getResponse());
        }
    }

    /**
     * @return void
     */
    public function testItInvokesDeletedMethodAndThrowsResponseFromResponsable(): void
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

            public function __construct(private readonly Responsable $result)
            {
            }

            public function deleted(stdClass $model, Request $request): Responsable
            {
                $this->model = $model;
                $this->request = $request;

                return $this->result;
            }
        };

        $model = new stdClass();
        $implementation = new HooksImplementation($target);

        try {
            $implementation->deleted($model, $this->request);
            $this->fail('No exception thrown.');
        } catch (HttpResponseException $ex) {
            $this->assertSame($model, $target->model);
            $this->assertSame($this->request, $target->request);
            $this->assertSame($response, $ex->getResponse());
        }
    }

    /**
     * @return void
     */
    public function testItInvokesUpdatingRelationshipMethod(): void
    {
        $target = new class {
            public ?stdClass $model = null;
            public ?Request $request = null;
            public ?QueryParameters $query = null;

            public function updatingBlogPosts(
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
        $implementation->updatingRelationship($model, 'blog-posts', $this->request, $this->query);

        $this->assertInstanceOf(UpdateRelationshipImplementation::class, $implementation);
        $this->assertSame($model, $target->model);
        $this->assertSame($this->request, $target->request);
        $this->assertSame($this->query, $target->query);
    }

    /**
     * @return void
     */
    public function testItInvokesUpdatingRelationshipMethodAndThrowsResponse(): void
    {
        $response = $this->createMock(Response::class);

        $target = new class($response) {
            public ?stdClass $model = null;
            public ?Request $request = null;
            public ?QueryParameters $query = null;

            public function __construct(private readonly Response $response)
            {
            }

            public function updatingComments(
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
            $implementation->updatingRelationship($model, 'comments', $this->request, $this->query);
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
    public function testItInvokesUpdatingRelationshipMethodAndThrowsResponseFromResponsable(): void
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

            public function updatingTags(
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
            $implementation->updatingRelationship($model, 'tags', $this->request, $this->query);
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
    public function testItInvokesUpdatedRelationshipMethod(): void
    {
        $target = new class {
            public ?stdClass $model = null;
            public ?ArrayObject $related = null;
            public ?Request $request = null;
            public ?QueryParameters $query = null;

            public function updatedBlogPosts(
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
        $implementation->updatedRelationship($model, 'blog-posts', $related, $this->request, $this->query);

        $this->assertSame($model, $target->model);
        $this->assertSame($related, $target->related);
        $this->assertSame($this->request, $target->request);
        $this->assertSame($this->query, $target->query);
    }

    /**
     * @return void
     */
    public function testItInvokesUpdatedRelationshipMethodAndThrowsResponse(): void
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

            public function updatedComments(
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
            $implementation->updatedRelationship($model, 'comments', $related, $this->request, $this->query);
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
    public function testItInvokesUpdatedRelationshipMethodAndThrowsResponseFromResponsable(): void
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

            public function updatedTags(
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
            $implementation->updatedRelationship($model, 'tags', $related, $this->request, $this->query);
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
    public function testItInvokesAttachingRelationshipMethod(): void
    {
        $target = new class {
            public ?stdClass $model = null;
            public ?Request $request = null;
            public ?QueryParameters $query = null;

            public function attachingBlogPosts(
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
        $implementation->attachingRelationship($model, 'blog-posts', $this->request, $this->query);

        $this->assertInstanceOf(AttachRelationshipImplementation::class, $implementation);
        $this->assertSame($model, $target->model);
        $this->assertSame($this->request, $target->request);
        $this->assertSame($this->query, $target->query);
    }

    /**
     * @return void
     */
    public function testItInvokesAttachingRelationshipMethodAndThrowsResponse(): void
    {
        $response = $this->createMock(Response::class);

        $target = new class($response) {
            public ?stdClass $model = null;
            public ?Request $request = null;
            public ?QueryParameters $query = null;

            public function __construct(private readonly Response $response)
            {
            }

            public function attachingComments(
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
            $implementation->attachingRelationship($model, 'comments', $this->request, $this->query);
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
    public function testItInvokesAttachingRelationshipMethodAndThrowsResponseFromResponsable(): void
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

            public function attachingTags(
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
            $implementation->attachingRelationship($model, 'tags', $this->request, $this->query);
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
    public function testItInvokesAttachedRelationshipMethod(): void
    {
        $target = new class {
            public ?stdClass $model = null;
            public ?ArrayObject $related = null;
            public ?Request $request = null;
            public ?QueryParameters $query = null;

            public function attachedBlogPosts(
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
        $implementation->attachedRelationship($model, 'blog-posts', $related, $this->request, $this->query);

        $this->assertSame($model, $target->model);
        $this->assertSame($related, $target->related);
        $this->assertSame($this->request, $target->request);
        $this->assertSame($this->query, $target->query);
    }

    /**
     * @return void
     */
    public function testItInvokesAttachedRelationshipMethodAndThrowsResponse(): void
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

            public function attachedComments(
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
            $implementation->attachedRelationship($model, 'comments', $related, $this->request, $this->query);
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
    public function testItInvokesAttachedRelationshipMethodAndThrowsResponseFromResponsable(): void
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

            public function attachedTags(
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
            $implementation->attachedRelationship($model, 'tags', $related, $this->request, $this->query);
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
    public function testItInvokesDetachingRelationshipMethod(): void
    {
        $target = new class {
            public ?stdClass $model = null;
            public ?Request $request = null;
            public ?QueryParameters $query = null;

            public function detachingBlogPosts(
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
        $implementation->detachingRelationship($model, 'blog-posts', $this->request, $this->query);

        $this->assertInstanceOf(DetachRelationshipImplementation::class, $implementation);
        $this->assertSame($model, $target->model);
        $this->assertSame($this->request, $target->request);
        $this->assertSame($this->query, $target->query);
    }

    /**
     * @return void
     */
    public function testItInvokesDetachingRelationshipMethodAndThrowsResponse(): void
    {
        $response = $this->createMock(Response::class);

        $target = new class($response) {
            public ?stdClass $model = null;
            public ?Request $request = null;
            public ?QueryParameters $query = null;

            public function __construct(private readonly Response $response)
            {
            }

            public function detachingComments(
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
            $implementation->detachingRelationship($model, 'comments', $this->request, $this->query);
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
    public function testItInvokesDetachingRelationshipMethodAndThrowsResponseFromResponsable(): void
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

            public function detachingTags(
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
            $implementation->detachingRelationship($model, 'tags', $this->request, $this->query);
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
    public function testItInvokesDetachedRelationshipMethod(): void
    {
        $target = new class {
            public ?stdClass $model = null;
            public ?ArrayObject $related = null;
            public ?Request $request = null;
            public ?QueryParameters $query = null;

            public function detachedBlogPosts(
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
        $implementation->detachedRelationship($model, 'blog-posts', $related, $this->request, $this->query);

        $this->assertSame($model, $target->model);
        $this->assertSame($related, $target->related);
        $this->assertSame($this->request, $target->request);
        $this->assertSame($this->query, $target->query);
    }

    /**
     * @return void
     */
    public function testItInvokesDetachedRelationshipMethodAndThrowsResponse(): void
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

            public function detachedComments(
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
            $implementation->detachedRelationship($model, 'comments', $related, $this->request, $this->query);
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
    public function testItInvokesDetachedRelationshipMethodAndThrowsResponseFromResponsable(): void
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

            public function detachedTags(
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
            $implementation->detachedRelationship($model, 'tags', $related, $this->request, $this->query);
            $this->fail('No exception thrown.');
        } catch (HttpResponseException $ex) {
            $this->assertSame($model, $target->model);
            $this->assertSame($related, $target->related);
            $this->assertSame($this->request, $target->request);
            $this->assertSame($this->query, $target->query);
            $this->assertSame($response, $ex->getResponse());
        }
    }
}
