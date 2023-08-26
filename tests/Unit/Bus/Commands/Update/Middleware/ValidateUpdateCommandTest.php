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

namespace LaravelJsonApi\Core\Tests\Unit\Bus\Commands\Update\Middleware;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Schema\Container as SchemaContainer;
use LaravelJsonApi\Contracts\Schema\Schema;
use LaravelJsonApi\Contracts\Validation\Container as ValidatorContainer;
use LaravelJsonApi\Contracts\Validation\Factory;
use LaravelJsonApi\Contracts\Validation\ResourceErrorFactory;
use LaravelJsonApi\Contracts\Validation\UpdateValidator;
use LaravelJsonApi\Core\Bus\Commands\Result;
use LaravelJsonApi\Core\Bus\Commands\Update\Middleware\ValidateUpdateCommand;
use LaravelJsonApi\Core\Bus\Commands\Update\UpdateCommand;
use LaravelJsonApi\Core\Document\ErrorList;
use LaravelJsonApi\Core\Document\Input\Values\ResourceObject;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Update;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;

class ValidateUpdateCommandTest extends TestCase
{
    /**
     * @var ResourceType
     */
    private ResourceType $type;

    /**
     * @var UpdateValidator&MockObject
     */
    private UpdateValidator $updateValidator;

    /**
     * @var Schema&MockObject
     */
    private Schema $schema;

    /**
     * @var ResourceErrorFactory&MockObject
     */
    private ResourceErrorFactory $errorFactory;

    /**
     * @var ValidateUpdateCommand
     */
    private ValidateUpdateCommand $middleware;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->type = new ResourceType('posts');

        $validators = $this->createMock(ValidatorContainer::class);
        $validators
            ->method('validatorsFor')
            ->with($this->identicalTo($this->type))
            ->willReturn($factory = $this->createMock(Factory::class));

        $factory
            ->method('update')
            ->willReturn($this->updateValidator = $this->createMock(UpdateValidator::class));

        $schemas = $this->createMock(SchemaContainer::class);
        $schemas
            ->method('schemaFor')
            ->with($this->identicalTo($this->type))
            ->willReturn($this->schema = $this->createMock(Schema::class));

        $this->middleware = new ValidateUpdateCommand(
            $validators,
            $schemas,
            $this->errorFactory = $this->createMock(ResourceErrorFactory::class),
        );
    }

    /**
     * @return void
     */
    public function testItPassesValidation(): void
    {
        $operation = new Update(
            target: null,
            data: new ResourceObject(type: $this->type, id: new ResourceId('123')),
        );

        $command = UpdateCommand::make(
            $request = $this->createMock(Request::class),
            $operation,
        )->withModel($model = new stdClass());

        $this->updateValidator
            ->expects($this->once())
            ->method('make')
            ->with($this->identicalTo($request), $this->identicalTo($model), $this->identicalTo($operation))
            ->willReturn($validator = $this->createMock(Validator::class));

        $this->updateValidator
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
            function (UpdateCommand $cmd) use ($command, $validated, $expected): Result {
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
        $operation = new Update(
            target: null,
            data: new ResourceObject(type: $this->type, id: new ResourceId('123')),
        );

        $command = UpdateCommand::make(
            $request = $this->createMock(Request::class),
            $operation,
        )->withModel($model = new stdClass());

        $this->updateValidator
            ->expects($this->once())
            ->method('make')
            ->with($this->identicalTo($request), $this->identicalTo($model), $this->identicalTo($operation))
            ->willReturn($validator = $this->createMock(Validator::class));

        $this->updateValidator
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
        $operation = new Update(
            target: null,
            data: new ResourceObject(type: $this->type, id: new ResourceId('123')),
        );

        $command = UpdateCommand::make(null, $operation)
            ->withModel($model = new stdClass())
            ->skipValidation();

        $this->updateValidator
            ->expects($this->once())
            ->method('extract')
            ->with($this->identicalTo($model), $this->identicalTo($operation))
            ->willReturn($validated = ['foo' => 'bar']);

        $this->updateValidator
            ->expects($this->never())
            ->method('make');

        $expected = Result::ok();

        $actual = $this->middleware->handle(
            $command,
            function (UpdateCommand $cmd) use ($command, $validated, $expected): Result {
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
        $operation = new Update(
            target: null,
            data: new ResourceObject(type: $this->type, id: new ResourceId('123')),
        );

        $command = UpdateCommand::make(null, $operation)
            ->withModel(new stdClass())
            ->withValidated($validated = ['foo' => 'bar']);

        $this->updateValidator
            ->expects($this->never())
            ->method($this->anything());

        $expected = Result::ok();

        $actual = $this->middleware->handle(
            $command,
            function (UpdateCommand $cmd) use ($command, $validated, $expected): Result {
                $this->assertSame($command, $cmd);
                $this->assertSame($validated, $cmd->validated());
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
    }
}
