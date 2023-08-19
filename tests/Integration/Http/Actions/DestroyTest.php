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

namespace LaravelJsonApi\Core\Tests\Integration\Http\Actions;

use Closure;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer;
use LaravelJsonApi\Contracts\Auth\Container as AuthContainer;
use LaravelJsonApi\Contracts\Http\Actions\Destroy as DestroyContract;
use LaravelJsonApi\Contracts\Resources\Container as ResourceContainer;
use LaravelJsonApi\Contracts\Routing\Route;
use LaravelJsonApi\Contracts\Schema\Container as SchemaContainer;
use LaravelJsonApi\Contracts\Store\Store as StoreContract;
use LaravelJsonApi\Contracts\Validation\Container as ValidatorContainer;
use LaravelJsonApi\Contracts\Validation\DestroyErrorFactory;
use LaravelJsonApi\Contracts\Validation\DestroyValidator;
use LaravelJsonApi\Contracts\Validation\Factory as ValidatorFactory;
use LaravelJsonApi\Core\Document\Input\Values\ResourceId;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Delete;
use LaravelJsonApi\Core\Http\Actions\Destroy;
use LaravelJsonApi\Core\Tests\Integration\TestCase;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;
use Symfony\Component\HttpFoundation\Response;

class DestroyTest extends TestCase
{
    /**
     * @var Route&MockObject
     */
    private Route&MockObject $route;

    /**
     * @var Request&MockObject
     */
    private Request&MockObject $request;

    /**
     * @var StoreContract&MockObject
     */
    private StoreContract&MockObject $store;

    /**
     * @var MockObject&ResourceContainer
     */
    private ResourceContainer&MockObject $resources;

    /**
     * @var ResponseFactory&MockObject
     */
    private ResponseFactory&MockObject $responseFactory;

    /**
     * @var DestroyContract
     */
    private DestroyContract $action;

    /**
     * @var array
     */
    private array $sequence = [];

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->container->bind(DestroyContract::class, Destroy::class);
        $this->container->instance(Route::class, $this->route = $this->createMock(Route::class));
        $this->container->instance(StoreContract::class, $this->store = $this->createMock(StoreContract::class));
        $this->container->instance(
            SchemaContainer::class,
            $this->createMock(SchemaContainer::class),
        );
        $this->container->instance(
            ResourceContainer::class,
            $this->resources = $this->createMock(ResourceContainer::class),
        );
        $this->container->instance(
            ResponseFactory::class,
            $this->responseFactory = $this->createMock(ResponseFactory::class),
        );

        $this->request = $this->createMock(Request::class);

        $this->action = $this->container->make(DestroyContract::class);
    }

    /**
     * @return void
     */
    public function testItDestroysById(): void
    {
        $this->route->method('resourceType')->willReturn('posts');
        $this->route->method('modelOrResourceId')->willReturn('123');

        $this->willNotLookupResourceId();
        $this->willNegotiateContent();
        $this->willFindModel('posts', '123', $model = new stdClass());
        $this->willAuthorize('posts', $model);
        $this->willValidate($model, 'posts', '123');
        $this->willDelete('posts', $model);
        $expected = $this->willHaveNoContent();

        $response = $this->action
            ->withHooks($this->withHooks($model))
            ->execute($this->request);

        $this->assertSame([
            'content-negotiation:accept',
            'find',
            'authorize',
            'validate',
            'hook:deleting',
            'delete',
            'hook:deleted',
        ], $this->sequence);
        $this->assertSame($expected, $response);
    }

    /**
     * @return void
     */
    public function testItDestroysModel(): void
    {
        $this->route
            ->expects($this->never())
            ->method($this->anything());

        $model = new \stdClass();

        $this->willNegotiateContent();
        $this->willNotFindModel();
        $this->willLookupResourceId($model, 'tags', '999');
        $this->willAuthorize('tags', $model);
        $this->willValidate($model, 'tags', '999',);
        $this->willDelete('tags', $model);
        $expected = $this->willHaveNoContent();

        $response = $this->action
            ->withTarget('tags', $model)
            ->execute($this->request);

        $this->assertSame([
            'content-negotiation:accept',
            'authorize',
            'validate',
            'delete',
        ], $this->sequence);
        $this->assertSame($response, $expected);
    }

    /**
     * @return void
     */
    private function willNegotiateContent(): void
    {
        $this->request
            ->expects($this->once())
            ->method('getAcceptableContentTypes')
            ->willReturnCallback(function (): array {
                $this->sequence[] = 'content-negotiation:accept';
                return ['application/vnd.api+json'];
            });
    }

    /**
     * @param string $type
     * @param string $id
     * @param object $model
     * @return void
     */
    private function willFindModel(string $type, string $id, object $model): void
    {
        $this->store
            ->expects($this->once())
            ->method('find')
            ->with(
                $this->callback(fn($actual): bool => $type === (string) $actual),
                $this->callback(fn($actual): bool => $id === (string) $actual)
            )
            ->willReturnCallback(function () use ($model) {
                $this->sequence[] = 'find';
                return $model;
            });
    }

    /**
     * @return void
     */
    private function willNotFindModel(): void
    {
        $this->store
            ->expects($this->never())
            ->method('find');
    }

    /**
     * @param string $type
     * @param object $model
     * @param bool $passes
     * @return void
     */
    private function willAuthorize(string $type, object $model, bool $passes = true): void
    {
        $this->container->instance(
            AuthContainer::class,
            $authorizers = $this->createMock(AuthContainer::class),
        );

        $authorizers
            ->expects($this->once())
            ->method('authorizerFor')
            ->with($type)
            ->willReturn($authorizer = $this->createMock(Authorizer::class));

        $authorizer
            ->expects($this->once())
            ->method('destroy')
            ->with($this->identicalTo($this->request), $this->identicalTo($model))
            ->willReturnCallback(function () use ($passes) {
                $this->sequence[] = 'authorize';
                return $passes;
            });
    }

    /**
     * @param object $model
     * @param string $type
     * @param string $id
     * @return void
     */
    private function willValidate(object $model, string $type, string $id): void
    {
        $this->container->instance(
            ValidatorContainer::class,
            $validators = $this->createMock(ValidatorContainer::class),
        );

        $this->container->instance(
            DestroyErrorFactory::class,
            $errorFactory = $this->createMock(DestroyErrorFactory::class),
        );

        $validators
            ->expects($this->atMost(2))
            ->method('validatorsFor')
            ->with($type)
            ->willReturn($validatorFactory = $this->createMock(ValidatorFactory::class));

        $validatorFactory
            ->expects($this->once())
            ->method('destroy')
            ->willReturn($destroyValidator = $this->createMock(DestroyValidator::class));

        $destroyValidator
            ->expects($this->once())
            ->method('make')
            ->with(
                $this->identicalTo($this->request),
                $this->identicalTo($model),
                $this->callback(function (Delete $op) use ($type, $id): bool {
                    $ref = $op->ref();
                    $this->assertSame($type, $ref?->type->value);
                    $this->assertSame($id, $ref?->id->value);
                    return true;
                }),
            )
            ->willReturn($validator = $this->createMock(Validator::class));

        $validator
            ->expects($this->once())
            ->method('fails')
            ->willReturnCallback(function () {
                $this->sequence[] = 'validate';
                return false;
            });

        $errorFactory
            ->expects($this->never())
            ->method($this->anything());
    }

    /**
     * @param string $type
     * @param object $model
     * @return void
     */
    private function willDelete(string $type, object $model): void
    {
        $this->store
            ->expects($this->once())
            ->method('delete')
            ->with($this->equalTo(new ResourceType($type)), $this->identicalTo($model))
            ->willReturnCallback(function () {
                $this->sequence[] = 'delete';
                return null;
            });
    }

    /**
     * @param object $model
     * @param string $type
     * @param string $id
     * @return void
     */
    private function willLookupResourceId(object $model, string $type, string $id): void
    {
        $this->resources
            ->expects($this->once())
            ->method('idForType')
            ->with(
                $this->callback(fn ($actual) => $type === (string) $actual),
                $this->identicalTo($model),
            )
            ->willReturn(new ResourceId($id));
    }

    /**
     * @return void
     */
    private function willNotLookupResourceId(): void
    {
        $this->resources
            ->expects($this->never())
            ->method($this->anything());
    }

    /**
     * @param object $expected
     * @return object
     */
    private function withHooks(object $expected): object
    {
        $seq = function (string $value): void {
            $this->sequence[] = $value;
        };

        return new class($seq, $this->request, $expected) {
            public function __construct(
                private readonly Closure $sequence,
                private readonly Request $request,
                private readonly object $model,
            ) {
            }

            public function deleting(object $model, Request $request): void
            {
                Assert::assertSame($this->model, $model);
                Assert::assertSame($this->request, $request);

                ($this->sequence)('hook:deleting');
            }

            public function deleted(object $model, Request $request): void
            {
                Assert::assertSame($this->model, $model);
                Assert::assertSame($this->request, $request);

                ($this->sequence)('hook:deleted');
            }
        };
    }

    /**
     * @return Response
     */
    private function willHaveNoContent(): Response
    {
        $this->responseFactory
            ->expects($this->once())
            ->method('noContent')
            ->willReturn($response = $this->createMock(Response::class));

        return $response;
    }
}
