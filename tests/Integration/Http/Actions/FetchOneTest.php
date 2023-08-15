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
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer;
use LaravelJsonApi\Contracts\Auth\Container as AuthContainer;
use LaravelJsonApi\Contracts\Http\Actions\FetchOne as FetchOneContract;
use LaravelJsonApi\Contracts\Query\QueryParameters;
use LaravelJsonApi\Contracts\Resources\Container as ResourceContainer;
use LaravelJsonApi\Contracts\Routing\Route;
use LaravelJsonApi\Contracts\Schema\Container as SchemaContainer;
use LaravelJsonApi\Contracts\Store\QueryOneBuilder;
use LaravelJsonApi\Contracts\Store\Store;
use LaravelJsonApi\Contracts\Validation\Container as ValidatorContainer;
use LaravelJsonApi\Contracts\Validation\Factory as ValidatorFactory;
use LaravelJsonApi\Contracts\Validation\QueryErrorFactory;
use LaravelJsonApi\Contracts\Validation\QueryOneValidator;
use LaravelJsonApi\Core\Document\Input\Values\ResourceId;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;
use LaravelJsonApi\Core\Http\Actions\FetchOne;
use LaravelJsonApi\Core\Tests\Integration\TestCase;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;

class FetchOneTest extends TestCase
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
     * @var Store&MockObject
     */
    private Store&MockObject $store;

    /**
     * @var MockObject&SchemaContainer
     */
    private SchemaContainer&MockObject $schemas;

    /**
     * @var ResourceContainer&MockObject
     */
    private ResourceContainer&MockObject $resources;

    /**
     * @var FetchOneContract
     */
    private FetchOneContract $action;

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

        $this->container->bind(FetchOneContract::class, FetchOne::class);
        $this->container->instance(Route::class, $this->route = $this->createMock(Route::class));
        $this->container->instance(Store::class, $this->store = $this->createMock(Store::class));
        $this->container->instance(
            SchemaContainer::class,
            $this->schemas = $this->createMock(SchemaContainer::class),
        );
        $this->container->instance(
            ResourceContainer::class,
            $this->resources = $this->createMock(ResourceContainer::class),
        );

        $this->request = $this->createMock(Request::class);

        $this->action = $this->container->make(FetchOneContract::class);
    }

    /**
     * @return void
     */
    public function testItFetchesOneById(): void
    {
        $this->route->method('resourceType')->willReturn('posts');
        $this->route->method('modelOrResourceId')->willReturn('123');

        $this->willNegotiateContent();
        $this->willFindModel('posts', '123', $authModel = new stdClass());
        $this->willAuthorize('posts', $authModel);
        $this->willValidate('posts', $queryParams = [
            'fields' => ['posts' => 'title,content,author'],
            'include' => 'author',
        ]);
        $this->willNotLookupResourceId();
        $model = $this->willQueryOne('posts', '123', $queryParams);

        $response = $this->action
            ->withHooks($this->withHooks($model, $queryParams))
            ->execute($this->request);

        $this->assertSame([
            'content-negotiation',
            'find',
            'authorize',
            'validate',
            'hook:reading',
            'query',
            'hook:read',
        ], $this->sequence);
        $this->assertSame($model, $response->data);
    }

    /**
     * @return void
     */
    public function testItFetchesOneByModel(): void
    {
        $this->route
            ->expects($this->never())
            ->method($this->anything());

        $authModel = new stdClass();

        $this->willLookupResourceId($authModel, 'comments', '456');
        $this->willNegotiateContent();
        $this->willNotFindModel();
        $this->willAuthorize('comments', $authModel);
        $this->willValidate('comments');
        $model = $this->willQueryOne('comments', '456');

        $response = $this->action
            ->withTarget('comments', $authModel)
            ->withHooks($this->withHooks($model))
            ->execute($this->request);

        $this->assertSame([
            'content-negotiation',
            'authorize',
            'validate',
            'hook:reading',
            'query',
            'hook:read',
        ], $this->sequence);
        $this->assertSame($model, $response->data);
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
                $this->sequence[] = 'content-negotiation';
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
            ->method('show')
            ->with($this->identicalTo($this->request), $this->identicalTo($model))
            ->willReturnCallback(function () use ($passes) {
                $this->sequence[] = 'authorize';
                return $passes;
            });
    }

    /**
     * @param string $type
     * @param array $validated
     * @return void
     */
    private function willValidate(string $type, array $validated = []): void
    {
        $this->container->instance(
            ValidatorContainer::class,
            $validators = $this->createMock(ValidatorContainer::class),
        );

        $this->container->instance(
            QueryErrorFactory::class,
            $errorFactory = $this->createMock(QueryErrorFactory::class),
        );

        $this->request
            ->expects($this->once())
            ->method('query')
            ->with(null)
            ->willReturn($params = ['foo' => 'bar']);

        $validators
            ->expects($this->once())
            ->method('validatorsFor')
            ->with($type)
            ->willReturn($validatorFactory = $this->createMock(ValidatorFactory::class));

        $validatorFactory
            ->expects($this->once())
            ->method('queryOne')
            ->willReturn($queryOneValidator = $this->createMock(QueryOneValidator::class));

        $queryOneValidator
            ->expects($this->once())
            ->method('make')
            ->with($this->identicalTo($this->request), $this->identicalTo($params))
            ->willReturn($validator = $this->createMock(Validator::class));

        $validator
            ->expects($this->once())
            ->method('fails')
            ->willReturnCallback(function () {
                $this->sequence[] = 'validate';
                return false;
            });

        $validator
            ->expects($this->once())
            ->method('validated')
            ->willReturn($validated);

        $errorFactory
            ->expects($this->never())
            ->method($this->anything());
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
     * @param string $type
     * @param string $id
     * @param array $queryParams
     * @return stdClass
     */
    private function willQueryOne(string $type, string $id, array $queryParams = []): object
    {
        $model = new stdClass();

        $this->store
            ->expects($this->once())
            ->method('queryOne')
            ->with(
                $this->callback(fn (ResourceType $actual): bool => $type === $actual->value),
                $this->callback(fn (ResourceId $actual): bool => $id === $actual->value),
            )
            ->willReturn($builder = $this->createMock(QueryOneBuilder::class));

        $builder
            ->expects($this->once())
            ->method('withQuery')
            ->with($this->callback(function (QueryParameters $actual) use ($queryParams): bool {
                $this->assertSame($actual->toQuery(), $queryParams);
                return true;
            }))
            ->willReturnSelf();

        $builder
            ->expects($this->once())
            ->method('first')
            ->willReturnCallback(function () use ($model) {
                $this->sequence[] = 'query';
                return $model;
            });

        return $model;
    }

    /**
     * @param object $model
     * @param array $queryParams
     * @return object
     */
    private function withHooks(object $model, array $queryParams = []): object
    {
        $seq = function (string $value): void {
            $this->sequence[] = $value;
        };

        return new class($seq, $this->request, $model, $queryParams) {
            public function __construct(
                private readonly Closure $sequence,
                private readonly Request $request,
                private readonly object $model,
                private readonly array $queryParams,
            ) {
            }

            public function reading(Request $request, QueryParameters $queryParams): void
            {
                Assert::assertSame($this->request, $request);
                Assert::assertSame($this->queryParams, $queryParams->toQuery());

                ($this->sequence)('hook:reading');
            }

            public function read(object $model, Request $request, QueryParameters $queryParams): void
            {
                Assert::assertSame($this->model, $model);
                Assert::assertSame($this->request, $request);
                Assert::assertSame($this->queryParams, $queryParams->toQuery());

                ($this->sequence)('hook:read');
            }
        };
    }
}
