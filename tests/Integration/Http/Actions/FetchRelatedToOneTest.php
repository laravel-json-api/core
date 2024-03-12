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
use LaravelJsonApi\Contracts\Auth\Authorizer;
use LaravelJsonApi\Contracts\Auth\Container as AuthContainer;
use LaravelJsonApi\Contracts\Http\Actions\FetchRelated as FetchRelatedContract;
use LaravelJsonApi\Contracts\Query\QueryParameters;
use LaravelJsonApi\Contracts\Resources\Container as ResourceContainer;
use LaravelJsonApi\Contracts\Routing\Route;
use LaravelJsonApi\Contracts\Schema\Container as SchemaContainer;
use LaravelJsonApi\Contracts\Schema\Relation;
use LaravelJsonApi\Contracts\Schema\Schema;
use LaravelJsonApi\Contracts\Store\QueryOneBuilder;
use LaravelJsonApi\Contracts\Store\Store;
use LaravelJsonApi\Contracts\Validation\Container as ValidatorContainer;
use LaravelJsonApi\Contracts\Validation\Factory as ValidatorFactory;
use LaravelJsonApi\Contracts\Validation\QueryErrorFactory;
use LaravelJsonApi\Contracts\Validation\QueryOneValidator;
use LaravelJsonApi\Core\Http\Actions\FetchRelated;
use LaravelJsonApi\Core\Query\Input\QueryRelated;
use LaravelJsonApi\Core\Tests\Integration\TestCase;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;

class FetchRelatedToOneTest extends TestCase
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
     * @var MockObject&ResourceContainer
     */
    private ResourceContainer&MockObject $resources;

    /**
     * @var FetchRelatedContract
     */
    private FetchRelatedContract $action;

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

        $this->container->bind(FetchRelatedContract::class, FetchRelated::class);
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

        $this->action = $this->container->make(FetchRelatedContract::class);
    }

    /**
     * @return void
     */
    public function testItFetchesToManyById(): void
    {
        $this->route->method('resourceType')->willReturn('posts');
        $this->route->method('modelOrResourceId')->willReturn('123');
        $this->route->method('fieldName')->willReturn('author');

        $validatedQueryParams = [
            'fields' => ['posts' => 'title,content,author'],
            'include' => 'profile',
        ];

        $this->willNotLookupResourceId();
        $this->willNegotiateContent();
        $this->withSchema('posts', 'author', 'users');
        $this->willFindModel('posts', '123', $model = new stdClass());
        $this->willAuthorize('posts', 'author', $model);
        $this->willValidate('users', new QueryRelated(
            new ResourceType('posts'),
            new ResourceId('123'),
            'author',
            ['foo' => 'bar'],
        ), $validatedQueryParams);
        $related = $this->willQueryToOne('posts', '123', 'author', $validatedQueryParams);

        $response = $this->action
            ->withHooks($this->withHooks($model, $related, $validatedQueryParams))
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
        $this->assertSame($model, $response->model);
        $this->assertSame('author', $response->fieldName);
        $this->assertSame($related, $response->related);
    }

    /**
     * @return void
     */
    public function testItFetchesOneByModel(): void
    {
        $this->route
            ->expects($this->never())
            ->method($this->anything());

        $validatedQueryParams = [
            'fields' => ['posts' => 'title,content,author'],
            'include' => 'profile',
        ];

        $this->willLookupResourceId($model = new stdClass(), 'comments', '456');
        $this->willNegotiateContent();
        $this->withSchema('comments', 'author', 'users');
        $this->willNotFindModel();
        $this->willAuthorize('comments', 'author', $model);
        $this->willValidate('users', new QueryRelated(
            new ResourceType('comments'),
            new ResourceId('456'),
            'author',
            ['foo' => 'bar'],
        ), $validatedQueryParams);

        $related = $this->willQueryToOne('comments', '456', 'author', $validatedQueryParams);

        $response = $this->action
            ->withTarget('comments', $model, 'author')
            ->withHooks($this->withHooks($model, $related, $validatedQueryParams))
            ->execute($this->request);

        $this->assertSame([
            'content-negotiation',
            'authorize',
            'validate',
            'hook:reading',
            'query',
            'hook:read',
        ], $this->sequence);
        $this->assertSame($model, $response->model);
        $this->assertSame('author', $response->fieldName);
        $this->assertSame($related, $response->related);
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
     * @param string $fieldName
     * @param string $inverse
     * @return void
     */
    private function withSchema(string $type, string $fieldName, string $inverse): void
    {
        $this->schemas
            ->expects($this->atLeastOnce())
            ->method('schemaFor')
            ->with($type)
            ->willReturn($schema = $this->createMock(Schema::class));

        $schema
            ->expects($this->atLeastOnce())
            ->method('relationship')
            ->with($fieldName)
            ->willReturn($relation = $this->createMock(Relation::class));

        $relation->method('inverse')->willReturn($inverse);
        $relation->method('toOne')->willReturn(true);
        $relation->method('toMany')->willReturn(false);
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
    private function willAuthorize(string $type, string $fieldName, object $model, bool $passes = true): void
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
            ->method('showRelated')
            ->with($this->identicalTo($this->request), $this->identicalTo($model), $this->identicalTo($fieldName))
            ->willReturnCallback(function () use ($passes) {
                $this->sequence[] = 'authorize';
                return $passes;
            });
    }

    /**
     * @param string $type
     * @param QueryRelated $input
     * @param array $validated
     * @return void
     */
    private function willValidate(string $type, QueryRelated $input, array $validated = []): void
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
            ->willReturn($input->parameters);

        $validators
            ->expects($this->once())
            ->method('validatorsFor')
            ->with($type)
            ->willReturn($validatorFactory = $this->createMock(ValidatorFactory::class));

        $validatorFactory
            ->expects($this->once())
            ->method('withRequest')
            ->with($this->identicalTo($this->request))
            ->willReturnSelf();

        $validatorFactory
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
     * @param string $fieldName
     * @param array $queryParams
     * @return object
     */
    private function willQueryToOne(string $type, string $id, string $fieldName, array $queryParams = []): object
    {
        $related = new \stdClass();

        $this->store
            ->expects($this->once())
            ->method('queryToOne')
            ->with(
                $this->equalTo(new ResourceType($type)),
                $this->equalTo(new ResourceId($id)),
                $this->identicalTo($fieldName),
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
            ->willReturnCallback(function () use ($related) {
                $this->sequence[] = 'query';
                return $related;
            });

        return $related;
    }

    /**
     * @param object $model
     * @param mixed $related
     * @param array $queryParams
     * @return object
     */
    private function withHooks(object $model, mixed $related, array $queryParams = []): object
    {
        $seq = function (string $value): void {
            $this->sequence[] = $value;
        };

        return new class($seq, $this->request, $model, $related, $queryParams) {
            public function __construct(
                private readonly Closure $sequence,
                private readonly Request $request,
                private readonly object $model,
                private readonly mixed $related,
                private readonly array $queryParams,
            ) {
            }

            public function readingRelatedAuthor(
                object $model,
                Request $request,
                QueryParameters $queryParams,
            ): void
            {
                Assert::assertSame($this->model, $model);
                Assert::assertSame($this->request, $request);
                Assert::assertSame($this->queryParams, $queryParams->toQuery());

                ($this->sequence)('hook:reading');
            }

            public function readRelatedAuthor(
                object $model,
                mixed $related,
                Request $request,
                QueryParameters $queryParams,
            ): void
            {
                Assert::assertSame($this->model, $model);
                Assert::assertSame($this->related, $related);
                Assert::assertSame($this->request, $request);
                Assert::assertSame($this->queryParams, $queryParams->toQuery());

                ($this->sequence)('hook:read');
            }
        };
    }
}
