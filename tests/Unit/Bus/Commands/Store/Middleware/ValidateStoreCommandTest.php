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

namespace LaravelJsonApi\Core\Tests\Unit\Bus\Commands\Store\Middleware;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Schema\Container as SchemaContainer;
use LaravelJsonApi\Contracts\Schema\Schema;
use LaravelJsonApi\Contracts\Validation\Container as ValidatorContainer;
use LaravelJsonApi\Contracts\Validation\Factory;
use LaravelJsonApi\Contracts\Validation\ResourceErrorFactory;
use LaravelJsonApi\Contracts\Validation\StoreValidator;
use LaravelJsonApi\Core\Bus\Commands\Result;
use LaravelJsonApi\Core\Bus\Commands\Store\Middleware\ValidateStoreCommand;
use LaravelJsonApi\Core\Bus\Commands\Store\StoreCommand;
use LaravelJsonApi\Core\Document\ErrorList;
use LaravelJsonApi\Core\Document\Input\Values\ResourceObject;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Create;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ValidateStoreCommandTest extends TestCase
{
    /**
     * @var ResourceType
     */
    private ResourceType $type;

    /**
     * @var ValidatorContainer&MockObject
     */
    private ValidatorContainer&MockObject $validators;

    /**
     * @var Schema&MockObject
     */
    private Schema $schema;

    /**
     * @var ResourceErrorFactory&MockObject
     */
    private ResourceErrorFactory $errorFactory;

    /**
     * @var ValidateStoreCommand
     */
    private ValidateStoreCommand $middleware;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->type = new ResourceType('posts');

        $schemas = $this->createMock(SchemaContainer::class);
        $schemas
            ->method('schemaFor')
            ->with($this->identicalTo($this->type))
            ->willReturn($this->schema = $this->createMock(Schema::class));

        $this->middleware = new ValidateStoreCommand(
            $this->validators = $this->createMock(ValidatorContainer::class),
            $schemas,
            $this->errorFactory = $this->createMock(ResourceErrorFactory::class),
        );
    }

    /**
     * @return void
     */
    public function testItPassesValidation(): void
    {
        $operation = new Create(
            target: null,
            data: new ResourceObject(type: $this->type),
        );

        $command = new StoreCommand(
            $request = $this->createMock(Request::class),
            $operation,
        );

        $storeValidator = $this->willValidate($request);

        $storeValidator
            ->expects($this->once())
            ->method('make')
            ->with($this->identicalTo($operation))
            ->willReturn($validator = $this->createMock(Validator::class));

        $storeValidator
            ->expects($this->never())
            ->method('extract');

        $validator
            ->expects($this->once())
            ->method('fails')
            ->willReturn(false);

        $validator
            ->expects($this->once())
            ->method('validated')
            ->willReturn($validated = ['foo' => 'bar']);

        $expected = Result::ok();

        $actual = $this->middleware->handle(
            $command,
            function (StoreCommand $cmd) use ($command, $validated, $expected): Result {
                $this->assertNotSame($command, $cmd);
                $this->assertSame($validated, $cmd->validated());
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
    }

    /**
     * @return void
     */
    public function testItFailsValidation(): void
    {
        $operation = new Create(
            target: null,
            data: new ResourceObject(type: $this->type),
        );

        $command = new StoreCommand(
            $request = $this->createMock(Request::class),
            $operation,
        );

        $storeValidator = $this->willValidate($request);

        $storeValidator
            ->expects($this->once())
            ->method('make')
            ->with($this->identicalTo($operation))
            ->willReturn($validator = $this->createMock(Validator::class));

        $storeValidator
            ->expects($this->never())
            ->method('extract');

        $validator
            ->expects($this->once())
            ->method('fails')
            ->willReturn(true);

        $this->errorFactory
            ->expects($this->once())
            ->method('make')
            ->with($this->identicalTo($this->schema), $this->identicalTo($validator))
            ->willReturn($errors = new ErrorList());

        $actual = $this->middleware->handle(
            $command,
            fn() => $this->fail('Not expecting next middleware to be called.'),
        );

        $this->assertTrue($actual->didFail());
        $this->assertSame($errors, $actual->errors());
    }

    /**
     * @return void
     */
    public function testItSetsValidatedDataIfNotValidating(): void
    {
        $operation = new Create(
            target: null,
            data: new ResourceObject(type: $this->type),
        );

        $command = StoreCommand::make($request = $this->createMock(Request::class), $operation)
            ->skipValidation();

        $storeValidator = $this->willValidate($request);

        $storeValidator
            ->expects($this->once())
            ->method('extract')
            ->with($this->identicalTo($operation))
            ->willReturn($validated = ['foo' => 'bar']);

        $storeValidator
            ->expects($this->never())
            ->method('make');

        $expected = Result::ok();

        $actual = $this->middleware->handle(
            $command,
            function (StoreCommand $cmd) use ($command, $validated, $expected): Result {
                $this->assertNotSame($command, $cmd);
                $this->assertSame($validated, $cmd->validated());
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
    }

    /**
     * @return void
     */
    public function testItDoesNotValidateIfAlreadyValidated(): void
    {
        $operation = new Create(
            target: null,
            data: new ResourceObject(type: $this->type),
        );

        $command = StoreCommand::make(null, $operation)
            ->withValidated($validated = ['foo' => 'bar']);

        $this->validators
            ->expects($this->never())
            ->method($this->anything());

        $expected = Result::ok();

        $actual = $this->middleware->handle(
            $command,
            function (StoreCommand $cmd) use ($command, $validated, $expected): Result {
                $this->assertSame($command, $cmd);
                $this->assertSame($validated, $cmd->validated());
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
    }

    /**
     * @param Request|null $request
     * @return MockObject&StoreValidator
     */
    private function willValidate(?Request $request): StoreValidator&MockObject
    {
        $this->validators
            ->expects($this->once())
            ->method('validatorsFor')
            ->with($this->identicalTo($this->type))
            ->willReturn($factory = $this->createMock(Factory::class));

        $factory
            ->expects($this->once())
            ->method('withRequest')
            ->with($this->identicalTo($request))
            ->willReturnSelf();

        $factory
            ->expects($this->once())
            ->method('store')
            ->willReturn($storeValidator = $this->createMock(StoreValidator::class));

        return $storeValidator;
    }
}
