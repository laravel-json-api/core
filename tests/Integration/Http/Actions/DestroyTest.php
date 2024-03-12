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
use LaravelJsonApi\Contracts\Http\Actions\Destroy as DestroyContract;
use LaravelJsonApi\Contracts\Resources\Container as ResourceContainer;
use LaravelJsonApi\Contracts\Routing\Route;
use LaravelJsonApi\Contracts\Schema\Container as SchemaContainer;
use LaravelJsonApi\Contracts\Store\Store as StoreContract;
use LaravelJsonApi\Contracts\Validation\Container as ValidatorContainer;
use LaravelJsonApi\Contracts\Validation\DeletionErrorFactory;
use LaravelJsonApi\Contracts\Validation\DeletionValidator;
use LaravelJsonApi\Contracts\Validation\Factory as ValidatorFactory;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Delete;
use LaravelJsonApi\Core\Http\Actions\Destroy;
use LaravelJsonApi\Core\Responses\NoContentResponse;
use LaravelJsonApi\Core\Tests\Integration\TestCase;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;

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
        $this->assertInstanceOf(NoContentResponse::class, $response);
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

        $response = $this->action
            ->withTarget('tags', $model)
            ->execute($this->request);

        $this->assertSame([
            'content-negotiation:accept',
            'authorize',
            'validate',
            'delete',
        ], $this->sequence);
        $this->assertInstanceOf(NoContentResponse::class, $response);
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
            DeletionErrorFactory::class,
            $errorFactory = $this->createMock(DeletionErrorFactory::class),
        );

        $validators
            ->expects($this->atMost(2))
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
            ->method('destroy')
            ->willReturn($destroyValidator = $this->createMock(DeletionValidator::class));

        $destroyValidator
            ->expects($this->once())
            ->method('make')
            ->with(
                $this->callback(function (Delete $op) use ($type, $id): bool {
                    $ref = $op->ref();
                    $this->assertSame($type, $ref?->type->value);
                    $this->assertSame($id, $ref?->id->value);
                    return true;
                }),
                $this->identicalTo($model),
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
}
