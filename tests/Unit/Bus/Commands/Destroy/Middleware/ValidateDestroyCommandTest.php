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

namespace LaravelJsonApi\Core\Tests\Unit\Bus\Commands\Destroy\Middleware;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Validation\Container as ValidatorContainer;
use LaravelJsonApi\Contracts\Validation\DestroyErrorFactory;
use LaravelJsonApi\Contracts\Validation\DestroyValidator;
use LaravelJsonApi\Contracts\Validation\Factory;
use LaravelJsonApi\Core\Bus\Commands\Destroy\DestroyCommand;
use LaravelJsonApi\Core\Bus\Commands\Destroy\Middleware\ValidateDestroyCommand;
use LaravelJsonApi\Core\Bus\Commands\Result;
use LaravelJsonApi\Core\Document\ErrorList;
use LaravelJsonApi\Core\Document\Input\Values\ResourceId;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Delete;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Ref;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;

class ValidateDestroyCommandTest extends TestCase
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
     * @var DestroyErrorFactory&MockObject
     */
    private DestroyErrorFactory&MockObject $errorFactory;

    /**
     * @var ValidateDestroyCommand
     */
    private ValidateDestroyCommand $middleware;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->type = new ResourceType('posts');

        $this->middleware = new ValidateDestroyCommand(
            $this->validators = $this->createMock(ValidatorContainer::class),
            $this->errorFactory = $this->createMock(DestroyErrorFactory::class),
        );
    }

    /**
     * @return void
     */
    public function testItPassesValidation(): void
    {
        $operation = new Delete(
            new Ref(type: $this->type, id: new ResourceId('123')),
        );

        $command = DestroyCommand::make(
            $request = $this->createMock(Request::class),
            $operation,
        )->withModel($model = new stdClass());

        $destroyValidator = $this->withDestroyValidator();

        $destroyValidator
            ->expects($this->once())
            ->method('make')
            ->with($this->identicalTo($request), $this->identicalTo($model), $this->identicalTo($operation))
            ->willReturn($validator = $this->createMock(Validator::class));

        $destroyValidator
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
            function (DestroyCommand $cmd) use ($command, $validated, $expected): Result {
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
        $operation = new Delete(
            new Ref(type: $this->type, id: new ResourceId('123')),
        );

        $command = DestroyCommand::make(
            $request = $this->createMock(Request::class),
            $operation,
        )->withModel($model = new stdClass());

        $destroyValidator = $this->withDestroyValidator();

        $destroyValidator
            ->expects($this->once())
            ->method('make')
            ->with($this->identicalTo($request), $this->identicalTo($model), $this->identicalTo($operation))
            ->willReturn($validator = $this->createMock(Validator::class));

        $destroyValidator
            ->expects($this->never())
            ->method('extract');

        $validator
            ->expects($this->once())
            ->method('fails')
            ->willReturn(true);

        $this->errorFactory
            ->expects($this->once())
            ->method('make')
            ->with($this->identicalTo($validator))
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
    public function testItHandlesMissingDestroyValidator(): void
    {
        $operation = new Delete(
            new Ref(type: $this->type, id: new ResourceId('123')),
        );

        $command = DestroyCommand::make(
            $this->createMock(Request::class),
            $operation,
        )->withModel(new stdClass());

        $this->withoutDestroyValidator();

        $expected = Result::ok();

        $actual = $this->middleware->handle(
            $command,
            function (DestroyCommand $cmd) use ($command, $expected): Result {
                $this->assertNotSame($command, $cmd);
                $this->assertSame([], $cmd->validated());
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
    }

    /**
     * @return void
     */
    public function testItSetsValidatedDataIfNotValidating(): void
    {
        $operation = new Delete(
            new Ref(type: $this->type, id: new ResourceId('123')),
        );

        $command = DestroyCommand::make(
            $this->createMock(Request::class),
            $operation,
        )->withModel($model = new stdClass())->skipValidation();

        $destroyValidator = $this->withDestroyValidator();

        $destroyValidator
            ->expects($this->once())
            ->method('extract')
            ->with($this->identicalTo($model), $this->identicalTo($operation))
            ->willReturn($validated = ['foo' => 'bar']);

        $destroyValidator
            ->expects($this->never())
            ->method('make');

        $expected = Result::ok();

        $actual = $this->middleware->handle(
            $command,
            function (DestroyCommand $cmd) use ($command, $validated, $expected): Result {
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
    public function testItSetsValidatedDataIfNotValidatingWithMissingValidator(): void
    {
        $operation = new Delete(
            new Ref(type: $this->type, id: new ResourceId('123')),
        );

        $command = DestroyCommand::make(
            $this->createMock(Request::class),
            $operation,
        )->withModel(new stdClass())->skipValidation();

        $this->withoutDestroyValidator();

        $expected = Result::ok();

        $actual = $this->middleware->handle(
            $command,
            function (DestroyCommand $cmd) use ($command, $expected): Result {
                $this->assertNotSame($command, $cmd);
                $this->assertSame([], $cmd->validated());
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
        $operation = new Delete(
            new Ref(type: $this->type, id: new ResourceId('123')),
        );

        $command = DestroyCommand::make(
            $this->createMock(Request::class),
            $operation,
        )->withModel(new stdClass())->withValidated($validated = ['foo' => 'bar']);

        $this->validators
            ->expects($this->never())
            ->method($this->anything());

        $expected = Result::ok();

        $actual = $this->middleware->handle(
            $command,
            function (DestroyCommand $cmd) use ($command, $validated, $expected): Result {
                $this->assertSame($command, $cmd);
                $this->assertSame($validated, $cmd->validated());
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
    }

    /**
     * @return MockObject&DestroyValidator
     */
    private function withDestroyValidator(): DestroyValidator&MockObject
    {
        $this->validators
            ->method('validatorsFor')
            ->with($this->identicalTo($this->type))
            ->willReturn($factory = $this->createMock(Factory::class));

        $factory
            ->method('destroy')
            ->willReturn($destroyValidator = $this->createMock(DestroyValidator::class));

        return $destroyValidator;
    }

    /**
     * @return void
     */
    private function withoutDestroyValidator(): void
    {
        $this->validators
            ->method('validatorsFor')
            ->with($this->identicalTo($this->type))
            ->willReturn($factory = $this->createMock(Factory::class));

        $factory
            ->method('destroy')
            ->willReturn(null);
    }
}