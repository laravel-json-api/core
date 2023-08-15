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
use Illuminate\Support\ValidatedInput;
use LaravelJsonApi\Contracts\Auth\Authorizer;
use LaravelJsonApi\Contracts\Auth\Container as AuthContainer;
use LaravelJsonApi\Contracts\Http\Actions\Update as UpdateActionContract;
use LaravelJsonApi\Contracts\Query\QueryParameters;
use LaravelJsonApi\Contracts\Resources\Container as ResourceContainer;
use LaravelJsonApi\Contracts\Routing\Route;
use LaravelJsonApi\Contracts\Schema\Container as SchemaContainer;
use LaravelJsonApi\Contracts\Spec\ResourceDocumentComplianceChecker;
use LaravelJsonApi\Contracts\Store\QueryOneBuilder;
use LaravelJsonApi\Contracts\Store\ResourceBuilder;
use LaravelJsonApi\Contracts\Store\Store as StoreContract;
use LaravelJsonApi\Contracts\Support\Result;
use LaravelJsonApi\Contracts\Validation\Container as ValidatorContainer;
use LaravelJsonApi\Contracts\Validation\Factory as ValidatorFactory;
use LaravelJsonApi\Contracts\Validation\QueryErrorFactory;
use LaravelJsonApi\Contracts\Validation\QueryOneValidator;
use LaravelJsonApi\Contracts\Validation\ResourceErrorFactory;
use LaravelJsonApi\Contracts\Validation\UpdateValidator;
use LaravelJsonApi\Core\Document\Input\Parsers\ResourceObjectParser;
use LaravelJsonApi\Core\Document\Input\Values\ResourceId;
use LaravelJsonApi\Core\Document\Input\Values\ResourceObject;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Update as UpdateOperation;
use LaravelJsonApi\Core\Http\Actions\Update;
use LaravelJsonApi\Core\Tests\Integration\TestCase;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;

class UpdateTest extends TestCase
{
    /**
     * @var Route&MockObject
     */
    private readonly Route&MockObject $route;

    /**
     * @var Request&MockObject
     */
    private readonly Request&MockObject $request;

    /**
     * @var StoreContract&MockObject
     */
    private readonly StoreContract&MockObject $store;

    /**
     * @var MockObject&SchemaContainer
     */
    private readonly SchemaContainer&MockObject $schemas;

    /**
     * @var MockObject&ResourceContainer
     */
    private readonly ResourceContainer&MockObject $resources;

    /**
     * @var ValidatorFactory&MockObject|null
     */
    private ?ValidatorFactory $validatorFactory = null;

    /**
     * @var UpdateActionContract
     */
    private readonly UpdateActionContract $action;

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

        $this->container->bind(UpdateActionContract::class, Update::class);
        $this->container->instance(Route::class, $this->route = $this->createMock(Route::class));
        $this->container->instance(StoreContract::class, $this->store = $this->createMock(StoreContract::class));
        $this->container->instance(
            SchemaContainer::class,
            $this->schemas = $this->createMock(SchemaContainer::class),
        );
        $this->container->instance(
            ResourceContainer::class,
            $this->resources = $this->createMock(ResourceContainer::class),
        );

        $this->request = $this->createMock(Request::class);

        $this->action = $this->container->make(UpdateActionContract::class);
    }

    /**
     * @return void
     */
    public function testItUpdatesOneById(): void
    {
        $this->route->method('resourceType')->willReturn('posts');
        $this->route->method('modelOrResourceId')->willReturn('123');

        $this->willNotLookupResourceId();
        $this->willNegotiateContent();
        $this->willFindModel('posts', '123', $initialModel = new stdClass());
        $this->willAuthorize('posts', $initialModel);
        $this->willBeCompliant('posts', '123');
        $this->willValidateQueryParams('posts', $queryParams = [
            'fields' => ['posts' => 'title,content,author'],
            'include' => 'author',
        ]);
        $resource = $this->willParseOperation('posts', '123');
        $this->willValidateOperation($initialModel, $resource, $validated = ['title' => 'Hello World']);
        $updatedModel = $this->willStore('posts', $validated);
        $model = $this->willQueryOne('posts', '123', $queryParams);

        $response = $this->action
            ->withHooks($this->withHooks($initialModel, $updatedModel, $queryParams))
            ->execute($this->request);

        $this->assertSame([
            'content-negotiation:supported',
            'content-negotiation:accept',
            'find',
            'authorize',
            'compliant',
            'validate:query',
            'parse',
            'validate:op',
            'hook:saving',
            'hook:updating',
            'store',
            'hook:updated',
            'hook:saved',
            'query',
        ], $this->sequence);
        $this->assertSame($model, $response->data);
        $this->assertFalse($response->created);
    }

    /**
     * @return void
     */
    public function testItUpdatesOneByModel(): void
    {
        $this->route
            ->expects($this->never())
            ->method($this->anything());

        $model = new \stdClass();

        $this->willNegotiateContent();
        $this->willNotFindModel();
        $this->willLookupResourceId($model, 'tags', '999');
        $this->willAuthorize('tags', $model);
        $this->willBeCompliant('tags', '999');
        $this->willValidateQueryParams('tags', $queryParams = []);
        $resource = $this->willParseOperation('tags', '999');
        $this->willValidateOperation($model, $resource, $validated = ['name' => 'Lindy Hop']);
        $this->willStore('tags', $validated, $model);
        $queriedModel = $this->willQueryOne('tags', '999', $queryParams);

        $response = $this->action
            ->withTarget('tags', $model)
            ->withHooks($this->withHooks($model, null, $queryParams))
            ->execute($this->request);

        $this->assertSame([
            'content-negotiation:supported',
            'content-negotiation:accept',
            'authorize',
            'compliant',
            'validate:query',
            'parse',
            'validate:op',
            'hook:saving',
            'hook:updating',
            'store',
            'hook:updated',
            'hook:saved',
            'query',
        ], $this->sequence);
        $this->assertSame($queriedModel, $response->data);
        $this->assertFalse($response->created);
    }

    /**
     * @return void
     */
    private function willNegotiateContent(): void
    {
        $this->request
            ->expects($this->once())
            ->method('header')
            ->with('CONTENT_TYPE')
            ->willReturnCallback(function (): string {
                $this->sequence[] = 'content-negotiation:supported';
                return 'application/vnd.api+json';
            });

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
            ->method('update')
            ->with($this->identicalTo($this->request), $this->identicalTo($model))
            ->willReturnCallback(function () use ($passes) {
                $this->sequence[] = 'authorize';
                return $passes;
            });
    }

    /**
     * @param string $type
     * @param string $id
     * @return void
     */
    private function willBeCompliant(string $type, string $id): void
    {
        $this->container->instance(
            ResourceDocumentComplianceChecker::class,
            $checker = $this->createMock(ResourceDocumentComplianceChecker::class),
        );

        $this->request
            ->expects($this->once())
            ->method('getContent')
            ->willReturn($content = '{}');

        $result = $this->createMock(Result::class);
        $result->method('didSucceed')->willReturn(true);
        $result->method('didFail')->willReturn(false);

        $checker
            ->expects($this->once())
            ->method('mustSee')
            ->with(
                $this->callback(fn (ResourceType $actual): bool => $type === $actual->value),
                $this->callback(fn (ResourceId $actual): bool => $id === $actual->value),
            )
            ->willReturnSelf();

        $checker
            ->expects($this->once())
            ->method('check')
            ->with($content)
            ->willReturnCallback(function () use ($result) {
                $this->sequence[] = 'compliant';
                return $result;
            });
    }

    /**
     * @param string $type
     * @param array $validated
     * @return void
     */
    private function willValidateQueryParams(string $type, array $validated = []): void
    {
        $this->container->instance(
            ValidatorContainer::class,
            $validators = $this->createMock(ValidatorContainer::class),
        );

        $this->container->instance(
            QueryErrorFactory::class,
            $errorFactory = $this->createMock(QueryErrorFactory::class),
        );

        $validators
            ->expects($this->atMost(2))
            ->method('validatorsFor')
            ->with($type)
            ->willReturn($this->validatorFactory = $this->createMock(ValidatorFactory::class));

        $this->validatorFactory
            ->expects($this->once())
            ->method('queryOne')
            ->willReturn($queryOneValidator = $this->createMock(QueryOneValidator::class));

        $queryOneValidator
            ->expects($this->once())
            ->method('forRequest')
            ->with($this->identicalTo($this->request))
            ->willReturn($validator = $this->createMock(Validator::class));

        $validator
            ->expects($this->once())
            ->method('fails')
            ->willReturnCallback(function () {
                $this->sequence[] = 'validate:query';
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
     * @param string $id
     * @return ResourceObject
     */
    private function willParseOperation(string $type, string $id): ResourceObject
    {
        $data = [
            'type' => $type,
            'id' => $id,
            'attributes' => [
                'foo' => 'bar',
            ],
        ];

        $resource = new ResourceObject(
            type: new ResourceType($type),
            attributes: $data['attributes'],
        );

        $this->container->instance(
            ResourceObjectParser::class,
            $parser = $this->createMock(ResourceObjectParser::class),
        );

        $this->request
            ->expects($this->atMost(2))
            ->method('json')
            ->willReturnCallback(fn (string $key) => match ($key) {
                'data' => $data,
                'meta' => [],
                default => throw new \RuntimeException('Unexpected JSON key: ' . $key),
            });

        $parser
            ->expects($this->once())
            ->method('parse')
            ->with($this->identicalTo($data))
            ->willReturnCallback(function () use ($resource) {
                $this->sequence[] = 'parse';
                return $resource;
            });

        return $resource;
    }

    /**
     * @param object $model
     * @param ResourceObject $resource
     * @param array $validated
     * @return void
     */
    private function willValidateOperation(object $model, ResourceObject $resource, array $validated): void
    {
        $this->container->instance(
            ResourceErrorFactory::class,
            $errorFactory = $this->createMock(ResourceErrorFactory::class),
        );

        $this->validatorFactory
            ->expects($this->once())
            ->method('update')
            ->willReturn($updateValidator = $this->createMock(UpdateValidator::class));

        $updateValidator
            ->expects($this->once())
            ->method('make')
            ->with(
                $this->identicalTo($this->request),
                $this->identicalTo($model),
                $this->callback(fn(UpdateOperation $op): bool => $op->data === $resource),
            )
            ->willReturn($validator = $this->createMock(Validator::class));

        $validator
            ->expects($this->once())
            ->method('fails')
            ->willReturnCallback(function () {
                $this->sequence[] = 'validate:op';
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
     * @param array $validated
     * @param object|null $model
     * @return stdClass
     */
    private function willStore(string $type, array $validated, object $model = null): object
    {
        $model = $model ?? new \stdClass();

        $this->store
            ->expects($this->once())
            ->method('update')
            ->with($this->equalTo(new ResourceType($type)))
            ->willReturn($builder = $this->createMock(ResourceBuilder::class));

        $builder
            ->expects($this->once())
            ->method('withRequest')
            ->with($this->identicalTo($this->request))
            ->willReturnSelf();

        $builder
            ->expects($this->once())
            ->method('store')
            ->with($this->equalTo(new ValidatedInput($validated)))
            ->willReturnCallback(function () use ($model) {
                $this->sequence[] = 'store';
                return $model;
            });

        return $model;
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
     * @param object $initialModel
     * @param object|null $updatedModel
     * @param array $queryParams
     * @return object
     */
    private function withHooks(object $initialModel, ?object $updatedModel,  array $queryParams = []): object
    {
        $seq = function (string $value): void {
            $this->sequence[] = $value;
        };

        return new class($seq, $this->request, $initialModel, $updatedModel ?? $initialModel, $queryParams) {
            public function __construct(
                private readonly Closure $sequence,
                private readonly Request $request,
                private readonly object $initialModel,
                private readonly object $updatedModel,
                private readonly array $queryParams,
            ) {
            }

            public function saving(object $model, Request $request, QueryParameters $queryParams): void
            {
                Assert::assertSame($this->initialModel, $model);
                Assert::assertSame($this->request, $request);
                Assert::assertSame($this->queryParams, $queryParams->toQuery());

                ($this->sequence)('hook:saving');
            }

            public function updating(object $model, Request $request, QueryParameters $queryParams): void
            {
                Assert::assertSame($this->initialModel, $model);
                Assert::assertSame($this->request, $request);
                Assert::assertSame($this->queryParams, $queryParams->toQuery());

                ($this->sequence)('hook:updating');
            }

            public function updated(object $model, Request $request, QueryParameters $queryParams): void
            {
                Assert::assertSame($this->updatedModel, $model);
                Assert::assertSame($this->request, $request);
                Assert::assertSame($this->queryParams, $queryParams->toQuery());

                ($this->sequence)('hook:updated');
            }

            public function saved(object $model, Request $request, QueryParameters $queryParams): void
            {
                Assert::assertSame($this->updatedModel, $model);
                Assert::assertSame($this->request, $request);
                Assert::assertSame($this->queryParams, $queryParams->toQuery());

                ($this->sequence)('hook:saved');
            }
        };
    }
}
