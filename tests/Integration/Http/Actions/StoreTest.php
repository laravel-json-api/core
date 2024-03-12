<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Integration\Http\Actions;

use Closure;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\ValidatedInput;
use LaravelJsonApi\Contracts\Auth\Authorizer;
use LaravelJsonApi\Contracts\Auth\Container as AuthContainer;
use LaravelJsonApi\Contracts\Http\Actions\Store as StoreActionContract;
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
use LaravelJsonApi\Contracts\Validation\CreationValidator;
use LaravelJsonApi\Core\Document\Input\Parsers\ResourceObjectParser;
use LaravelJsonApi\Core\Document\Input\Values\ResourceObject;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Create as StoreOperation;
use LaravelJsonApi\Core\Http\Actions\Store;
use LaravelJsonApi\Core\Query\Input\WillQueryOne;
use LaravelJsonApi\Core\Tests\Integration\TestCase;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;

class StoreTest extends TestCase
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
     * @var MockObject&SchemaContainer
     */
    private SchemaContainer&MockObject $schemas;

    /**
     * @var MockObject&ResourceContainer
     */
    private ResourceContainer&MockObject $resources;

    /**
     * @var ValidatorFactory&MockObject|null
     */
    private ?ValidatorFactory $validatorFactory = null;

    /**
     * @var StoreActionContract
     */
    private StoreActionContract $action;

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

        $this->container->bind(StoreActionContract::class, Store::class);
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

        $this->action = $this->container->make(StoreActionContract::class);
    }

    /**
     * @return void
     */
    public function test(): void
    {
        $this->route->method('resourceType')->willReturn('posts');

        $validatedQueryParams = [
            'fields' => ['posts' => 'title,content,author'],
            'include' => 'author',
        ];

        $this->willNegotiateContent();
        $this->willAuthorize('posts', 'App\Models\Post');
        $this->willBeCompliant('posts');
        $this->willValidateQueryParams('posts', new WillQueryOne(
            new ResourceType('posts'),
            ['foo' => 'bar'],
        ), $validatedQueryParams);
        $resource = $this->willParseOperation('posts');
        $this->willValidateOperation($resource, $validated = ['title' => 'Hello World']);
        $createdModel = $this->willStore('posts', $validated);
        $this->willLookupResourceId($createdModel, 'posts', '123');
        $model = $this->willQueryOne('posts', '123', $validatedQueryParams);

        $response = $this->action
            ->withHooks($this->withHooks($createdModel, $validatedQueryParams))
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
            'hook:creating',
            'store',
            'hook:created',
            'hook:saved',
            'lookup-id',
            'query',
        ], $this->sequence);
        $this->assertSame($model, $response->data);
        $this->assertTrue($response->created);
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
     * @param string $modelClass
     * @param bool $passes
     * @return void
     */
    private function willAuthorize(string $type, string $modelClass, bool $passes = true): void
    {
        $this->container->instance(
            AuthContainer::class,
            $authorizers = $this->createMock(AuthContainer::class),
        );

        $this->schemas
            ->expects($this->once())
            ->method('modelClassFor')
            ->with($type)
            ->willReturn($modelClass);

        $authorizers
            ->expects($this->once())
            ->method('authorizerFor')
            ->with($type)
            ->willReturn($authorizer = $this->createMock(Authorizer::class));

        $authorizer
            ->expects($this->once())
            ->method('store')
            ->with($this->identicalTo($this->request), $modelClass)
            ->willReturnCallback(function () use ($passes) {
                $this->sequence[] = 'authorize';
                return $passes;
            });
    }

    /**
     * @param string $type
     * @return void
     */
    private function willBeCompliant(string $type): void
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
                null,
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
     * @param WillQueryOne $input
     * @param array $validated
     * @return void
     */
    private function willValidateQueryParams(string $type, WillQueryOne $input, array $validated = []): void
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
            ->willReturn($input->parameters);

        $validators
            ->expects($this->atMost(2))
            ->method('validatorsFor')
            ->with($type)
            ->willReturn($this->validatorFactory = $this->createMock(ValidatorFactory::class));

        $this->validatorFactory
            ->expects($this->atMost(2))
            ->method('withRequest')
            ->willReturnSelf();

        $this->validatorFactory
            ->expects($this->once())
            ->method('queryOne')
            ->willReturn($queryOneValidator = $this->createMock(QueryOneValidator::class));

        $queryOneValidator
            ->expects($this->once())
            ->method('make')
            ->with($this->equalTo($input))
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
     * @return ResourceObject
     */
    private function willParseOperation(string $type): ResourceObject
    {
        $data = [
            'type' => $type,
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
     * @param ResourceObject $resource
     * @param array $validated
     * @return void
     */
    private function willValidateOperation(ResourceObject $resource, array $validated): void
    {
        $this->container->instance(
            ResourceErrorFactory::class,
            $errorFactory = $this->createMock(ResourceErrorFactory::class),
        );

        $this->validatorFactory
            ->expects($this->once())
            ->method('store')
            ->willReturn($storeValidator = $this->createMock(CreationValidator::class));

        $storeValidator
            ->expects($this->once())
            ->method('make')
            ->with($this->callback(fn(StoreOperation $op): bool => $op->data === $resource))
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
     * @return stdClass
     */
    private function willStore(string $type, array $validated): object
    {
        $model = new \stdClass();

        $this->store
            ->expects($this->once())
            ->method('create')
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
            ->willReturnCallback(function () use ($id) {
                $this->sequence[] = 'lookup-id';
                return new ResourceId($id);
            });
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

            public function saving(mixed $model, Request $request, QueryParameters $queryParams): void
            {
                Assert::assertNull($model);
                Assert::assertSame($this->request, $request);
                Assert::assertSame($this->queryParams, $queryParams->toQuery());

                ($this->sequence)('hook:saving');
            }

            public function creating(Request $request, QueryParameters $queryParams): void
            {
                Assert::assertSame($this->request, $request);
                Assert::assertSame($this->queryParams, $queryParams->toQuery());

                ($this->sequence)('hook:creating');
            }

            public function created(object $model, Request $request, QueryParameters $queryParams): void
            {
                Assert::assertSame($this->model, $model);
                Assert::assertSame($this->request, $request);
                Assert::assertSame($this->queryParams, $queryParams->toQuery());

                ($this->sequence)('hook:created');
            }

            public function saved(object $model, Request $request, QueryParameters $queryParams): void
            {
                Assert::assertSame($this->model, $model);
                Assert::assertSame($this->request, $request);
                Assert::assertSame($this->queryParams, $queryParams->toQuery());

                ($this->sequence)('hook:saved');
            }
        };
    }
}
