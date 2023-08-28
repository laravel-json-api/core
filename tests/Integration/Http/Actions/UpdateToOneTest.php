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
use LaravelJsonApi\Contracts\Http\Actions\UpdateRelationship as UpdateRelationshipActionContract;
use LaravelJsonApi\Contracts\Query\QueryParameters;
use LaravelJsonApi\Contracts\Resources\Container as ResourceContainer;
use LaravelJsonApi\Contracts\Routing\Route;
use LaravelJsonApi\Contracts\Schema\Container as SchemaContainer;
use LaravelJsonApi\Contracts\Schema\Relation;
use LaravelJsonApi\Contracts\Schema\Schema;
use LaravelJsonApi\Contracts\Spec\RelationshipDocumentComplianceChecker;
use LaravelJsonApi\Contracts\Store\QueryOneBuilder;
use LaravelJsonApi\Contracts\Store\Store as StoreContract;
use LaravelJsonApi\Contracts\Store\ToOneBuilder;
use LaravelJsonApi\Contracts\Support\Result;
use LaravelJsonApi\Contracts\Validation\Container as ValidatorContainer;
use LaravelJsonApi\Contracts\Validation\Factory as ValidatorFactory;
use LaravelJsonApi\Contracts\Validation\QueryErrorFactory;
use LaravelJsonApi\Contracts\Validation\QueryOneValidator;
use LaravelJsonApi\Contracts\Validation\RelationshipValidator;
use LaravelJsonApi\Contracts\Validation\ResourceErrorFactory;
use LaravelJsonApi\Core\Document\Input\Parsers\ResourceIdentifierOrListOfIdentifiersParser;
use LaravelJsonApi\Core\Document\Input\Values\ResourceIdentifier;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\UpdateToOne;
use LaravelJsonApi\Core\Http\Actions\UpdateRelationship;
use LaravelJsonApi\Core\Query\Input\QueryRelationship;
use LaravelJsonApi\Core\Tests\Integration\TestCase;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;

class UpdateToOneTest extends TestCase
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
     * @var array
     */
    private array $validatorFactories = [];

    /**
     * @var UpdateRelationshipActionContract
     */
    private UpdateRelationshipActionContract $action;

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

        $this->container->bind(UpdateRelationshipActionContract::class, UpdateRelationship::class);
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

        $this->container->instance(
            ValidatorContainer::class,
            $validators = $this->createMock(ValidatorContainer::class),
        );

        $validators->method('validatorsFor')->willReturnCallback(
            fn (ResourceType|string $type) =>
                $this->validatorFactories[(string) $type] ?? throw new \RuntimeException('Unexpected type: ' . $type),
        );

        $this->action = $this->container->make(UpdateRelationshipActionContract::class);
    }

    /**
     * @return void
     */
    public function testItUpdatesOneById(): void
    {
        $this->route->method('resourceType')->willReturn('posts');
        $this->route->method('modelOrResourceId')->willReturn('123');
        $this->route->method('fieldName')->willReturn('author');

        $validatedQueryParams = [
            'fields' => ['posts' => 'title,content,author'],
            'include' => 'author',
        ];

        $this->withSchema('posts', 'author', 'users');
        $this->willNotLookupResourceId();
        $this->willNegotiateContent();
        $this->willFindModel('posts', '123', $post = new stdClass());
        $this->willAuthorize('posts', $post, 'author');
        $this->willBeCompliant('posts', 'author');
        $this->willValidateQueryParams('users', new QueryRelationship(
            new ResourceType('posts'),
            new ResourceId('123'),
            'author',
            ['foo' => 'bar'],
        ), $validatedQueryParams);
        $identifier = $this->willParseOperation('posts', '123');
        $this->willValidateOperation('posts', $post, $identifier, $validated = [
            'author' => [
                'type' => 'users',
                'id' => 'blah',
            ],
        ]);
        $modifiedRelated = $this->willModify('posts', $post, 'author', $validated['author']);
        $related = $this->willQueryToOne('posts', '123', 'author', $validatedQueryParams);

        $response = $this->action
            ->withHooks($this->withHooks($post, $modifiedRelated, $validatedQueryParams))
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
            'hook:updating',
            'modify',
            'hook:updated',
            'query',
        ], $this->sequence);
        $this->assertSame($post, $response->model);
        $this->assertSame('author', $response->fieldName);
        $this->assertSame($related, $response->related);
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

        $validatedQueryParams = [
            'fields' => ['posts' => 'title,content,author'],
            'include' => 'author',
        ];

        $this->withSchema('posts', 'author', 'users');
        $this->willNegotiateContent();
        $this->willNotFindModel();
        $this->willLookupResourceId($model, 'posts', '999');
        $this->willAuthorize('posts', $model, 'author');
        $this->willBeCompliant('posts', 'author');
        $this->willValidateQueryParams('users', new QueryRelationship(
            new ResourceType('posts'),
            new ResourceId('999'),
            'author',
            ['foo' => 'bar'],
        ), $validatedQueryParams);
        $identifier = $this->willParseOperation('posts', '999');
        $this->willValidateOperation('posts', $model, $identifier, $validated = [
            'author' => [
                'type' => 'users',
                'id' => 'XYZ',
            ],
        ]);
        $this->willModify('posts', $model, 'author', $validated['author']);
        $related = $this->willQueryToOne('posts', '999', 'author', $validatedQueryParams);

        $response = $this->action
            ->withTarget('posts', $model, 'author')
            ->execute($this->request);

        $this->assertSame([
            'content-negotiation:supported',
            'content-negotiation:accept',
            'authorize',
            'compliant',
            'validate:query',
            'parse',
            'validate:op',
            'modify',
            'query',
        ], $this->sequence);
        $this->assertSame($model, $response->model);
        $this->assertSame('author', $response->fieldName);
        $this->assertSame($related, $response->related);
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
            ->method('schemaFor')
            ->with($this->callback(fn ($actual) => $type === (string) $actual))
            ->willReturn($schema = $this->createMock(Schema::class));

        $schema
            ->method('relationship')
            ->with($fieldName)
            ->willReturn($relation = $this->createMock(Relation::class));

        $relation->method('inverse')->willReturn($inverse);
        $relation->method('toOne')->willReturn(true);
        $relation->method('toMany')->willReturn(false);
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
     * @param string $fieldName
     * @param bool $passes
     * @return void
     */
    private function willAuthorize(string $type, object $model, string $fieldName, bool $passes = true): void
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
            ->method('updateRelationship')
            ->with($this->identicalTo($this->request), $this->identicalTo($model), $this->identicalTo($fieldName))
            ->willReturnCallback(function () use ($passes) {
                $this->sequence[] = 'authorize';
                return $passes;
            });
    }

    /**
     * @param string $type
     * @param string $fieldName
     * @return void
     */
    private function willBeCompliant(string $type, string $fieldName): void
    {
        $this->container->instance(
            RelationshipDocumentComplianceChecker::class,
            $checker = $this->createMock(RelationshipDocumentComplianceChecker::class),
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
                $this->identicalTo($fieldName),
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
     * @param string $inverse
     * @param QueryRelationship $input
     * @param array $validated
     * @return void
     */
    private function willValidateQueryParams(string $inverse, QueryRelationship $input, array $validated = []): void
    {
        $this->container->instance(
            QueryErrorFactory::class,
            $errorFactory = $this->createMock(QueryErrorFactory::class),
        );

        $validatorFactory = $this->createMock(ValidatorFactory::class);
        $this->validatorFactories[$inverse] = $validatorFactory;

        $this->request
            ->expects($this->once())
            ->method('query')
            ->willReturn($input->parameters);

        $validatorFactory
            ->expects($this->once())
            ->method('queryOne')
            ->willReturn($queryOneValidator = $this->createMock(QueryOneValidator::class));

        $queryOneValidator
            ->expects($this->once())
            ->method('make')
            ->with($this->identicalTo($this->request), $this->equalTo($input))
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
     * @return ResourceIdentifier
     */
    private function willParseOperation(string $type, string $id): ResourceIdentifier
    {
        $data = [
            'type' => $type,
            'id' => $id,
        ];

        $identifier = new ResourceIdentifier(
            type: new ResourceType($type),
            id: new ResourceId($id),
        );

        $this->container->instance(
            ResourceIdentifierOrListOfIdentifiersParser::class,
            $parser = $this->createMock(ResourceIdentifierOrListOfIdentifiersParser::class),
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
            ->method('nullable')
            ->with($this->identicalTo($data))
            ->willReturnCallback(function () use ($identifier) {
                $this->sequence[] = 'parse';
                return $identifier;
            });

        return $identifier;
    }

    /**
     * @param string $type
     * @param object $model
     * @param ResourceIdentifier $identifier
     * @param array $validated
     * @return void
     */
    private function willValidateOperation(
        string $type,
        object $model,
        ResourceIdentifier $identifier,
        array $validated
    ): void
    {
        $this->container->instance(
            ResourceErrorFactory::class,
            $errorFactory = $this->createMock(ResourceErrorFactory::class),
        );

        $validatorFactory = $this->createMock(ValidatorFactory::class);
        $this->validatorFactories[$type] = $validatorFactory;

        $validatorFactory
            ->expects($this->once())
            ->method('relation')
            ->willReturn($relationshipValidator = $this->createMock(RelationshipValidator::class));

        $relationshipValidator
            ->expects($this->once())
            ->method('make')
            ->with(
                $this->identicalTo($this->request),
                $this->identicalTo($model),
                $this->callback(fn(UpdateToOne $op): bool => $op->data === $identifier),
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
     * @param object $model
     * @param string $fieldName
     * @param array $validated
     * @return stdClass
     */
    private function willModify(string $type, object $model, string $fieldName, array $validated): object
    {
        $related = new \stdClass();

        $this->store
            ->expects($this->once())
            ->method('modifyToOne')
            ->with($type, $this->identicalTo($model), $fieldName)
            ->willReturn($builder = $this->createMock(ToOneBuilder::class));

        $builder
            ->expects($this->once())
            ->method('withRequest')
            ->with($this->identicalTo($this->request))
            ->willReturnSelf();

        $builder
            ->expects($this->once())
            ->method('associate')
            ->with($this->identicalTo($validated))
            ->willReturnCallback(function () use ($related) {
                $this->sequence[] = 'modify';
                return $related;
            });

        return $related;
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
     * @return stdClass
     */
    private function willQueryToOne(string $type, string $id, string $fieldName, array $queryParams = []): object
    {
        $related = new stdClass();

        $this->store
            ->expects($this->once())
            ->method('queryToOne')
            ->with($type, $id, $fieldName)
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
    private function withHooks(object $model, mixed $related,  array $queryParams = []): object
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

            public function updatingAuthor(
                object $model,
                Request $request,
                QueryParameters $queryParams,
            ): void
            {
                Assert::assertSame($this->model, $model);
                Assert::assertSame($this->request, $request);
                Assert::assertSame($this->queryParams, $queryParams->toQuery());

                ($this->sequence)('hook:updating');
            }

            public function updatedAuthor(
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

                ($this->sequence)('hook:updated');
            }
        };
    }
}
