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

use ArrayObject;
use Closure;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Auth\Authorizer;
use LaravelJsonApi\Contracts\Auth\Container as AuthContainer;
use LaravelJsonApi\Contracts\Http\Actions\FetchMany as FetchManyContract;
use LaravelJsonApi\Contracts\Query\QueryParameters;
use LaravelJsonApi\Contracts\Routing\Route;
use LaravelJsonApi\Contracts\Schema\Container as SchemaContainer;
use LaravelJsonApi\Contracts\Store\Store;
use LaravelJsonApi\Contracts\Validation\Container as ValidatorContainer;
use LaravelJsonApi\Contracts\Validation\Factory as ValidatorFactory;
use LaravelJsonApi\Contracts\Validation\QueryErrorFactory;
use LaravelJsonApi\Contracts\Validation\QueryManyValidator;
use LaravelJsonApi\Core\Http\Actions\FetchMany;
use LaravelJsonApi\Core\Query\Input\QueryMany;
use LaravelJsonApi\Core\Store\QueryAllHandler;
use LaravelJsonApi\Core\Tests\Integration\TestCase;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;

class FetchManyTest extends TestCase
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
     * @var FetchManyContract
     */
    private FetchManyContract $action;

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

        $this->container->bind(FetchManyContract::class, FetchMany::class);
        $this->container->instance(Route::class, $this->route = $this->createMock(Route::class));
        $this->container->instance(Store::class, $this->store = $this->createMock(Store::class));
        $this->container->instance(SchemaContainer::class, $this->createMock(SchemaContainer::class));

        $this->request = $this->createMock(Request::class);

        $this->action = $this->container->make(FetchManyContract::class);
    }

    /**
     * @return void
     */
    public function testItFetchesMany(): void
    {
        $this->route->method('resourceType')->willReturn('posts');

        $this->willNegotiateContent();
        $this->willAuthorize('posts');
        $this->willValidate('posts', $queryParams = [
            'fields' => ['posts' => 'title,content,author'],
            'include' => 'author',
        ]);
        $models = $this->willQueryMany('posts', $queryParams);

        $response = $this->action
            ->withHooks($this->withHooks($models, $queryParams))
            ->execute($this->request);

        $this->assertSame([
            'content-negotiation',
            'authorize',
            'validate',
            'hook:searching',
            'query',
            'hook:searched',
        ], $this->sequence);
        $this->assertSame($models, $response->data);
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
     * @param bool $passes
     * @return void
     */
    private function willAuthorize(string $type, bool $passes = true): void
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
            ->method('index')
            ->with($this->identicalTo($this->request))
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

        $input = new QueryMany(new ResourceType($type), ['foo' => 'bar']);

        $this->request
            ->expects($this->once())
            ->method('query')
            ->with(null)
            ->willReturn($input->parameters);

        $validators
            ->expects($this->once())
            ->method('validatorsFor')
            ->with($type)
            ->willReturn($validatorFactory = $this->createMock(ValidatorFactory::class));

        $validatorFactory
            ->expects($this->once())
            ->method('queryMany')
            ->willReturn($queryManyValidator = $this->createMock(QueryManyValidator::class));

        $queryManyValidator
            ->expects($this->once())
            ->method('make')
            ->with($this->identicalTo($this->request), $this->equalTo($input))
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
     * @param string $type
     * @param array $queryParams
     * @return ArrayObject
     */
    private function willQueryMany(string $type, array $queryParams = []): ArrayObject
    {
        $models = new ArrayObject();

        $this->store
            ->expects($this->once())
            ->method('queryAll')
            ->with($this->equalTo(new ResourceType($type)))
            ->willReturn($builder = $this->createMock(QueryAllHandler::class));

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
            ->method('firstOrPaginate')
            ->willReturnCallback(function () use ($models) {
                $this->sequence[] = 'query';
                return $models;
            });

        return $models;
    }

    /**
     * @param ArrayObject $models
     * @param array $queryParams
     * @return object
     */
    private function withHooks(ArrayObject $models, array $queryParams = []): object
    {
        $seq = function (string $value): void {
            $this->sequence[] = $value;
        };

        return new class($seq, $this->request, $models, $queryParams) {
            public function __construct(
                private readonly Closure $sequence,
                private readonly Request $request,
                private readonly object $models,
                private readonly array $queryParams,
            ) {
            }

            public function searching(Request $request, QueryParameters $queryParams): void
            {
                Assert::assertSame($this->request, $request);
                Assert::assertSame($this->queryParams, $queryParams->toQuery());

                ($this->sequence)('hook:searching');
            }

            public function searched(object $models, Request $request, QueryParameters $queryParams): void
            {
                Assert::assertSame($this->models, $models);
                Assert::assertSame($this->request, $request);
                Assert::assertSame($this->queryParams, $queryParams->toQuery());

                ($this->sequence)('hook:searched');
            }
        };
    }
}
