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

namespace LaravelJsonApi\Core\Tests\Unit\Bus\Commands\Middleware;

use Closure;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Schema\Container as SchemaContainer;
use LaravelJsonApi\Contracts\Schema\Schema;
use LaravelJsonApi\Contracts\Validation\Container as ValidatorContainer;
use LaravelJsonApi\Contracts\Validation\Factory;
use LaravelJsonApi\Contracts\Validation\RelationshipValidator;
use LaravelJsonApi\Contracts\Validation\ResourceErrorFactory;
use LaravelJsonApi\Core\Bus\Commands\AttachRelationship\AttachRelationshipCommand;
use LaravelJsonApi\Core\Bus\Commands\Middleware\ValidateRelationshipCommand;
use LaravelJsonApi\Core\Bus\Commands\Result;
use LaravelJsonApi\Core\Bus\Commands\UpdateRelationship\UpdateRelationshipCommand;
use LaravelJsonApi\Core\Document\ErrorList;
use LaravelJsonApi\Core\Document\Input\Values\ListOfResourceIdentifiers;
use LaravelJsonApi\Core\Document\Input\Values\ResourceId;
use LaravelJsonApi\Core\Document\Input\Values\ResourceIdentifier;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\UpdateToMany;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\UpdateToOne;
use LaravelJsonApi\Core\Extensions\Atomic\Values\OpCodeEnum;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Ref;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ValidateRelationshipCommandTest extends TestCase
{
    /**
     * @var ResourceType
     */
    private ResourceType $type;

    /**
     * @var RelationshipValidator&MockObject
     */
    private RelationshipValidator $relationshipValidator;

    /**
     * @var Schema&MockObject
     */
    private Schema $schema;

    /**
     * @var ResourceErrorFactory&MockObject
     */
    private ResourceErrorFactory $errorFactory;

    /**
     * @var ValidateRelationshipCommand
     */
    private ValidateRelationshipCommand $middleware;

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
            ->method('relation')
            ->willReturn($this->relationshipValidator = $this->createMock(RelationshipValidator::class));

        $schemas = $this->createMock(SchemaContainer::class);
        $schemas
            ->method('schemaFor')
            ->with($this->identicalTo($this->type))
            ->willReturn($this->schema = $this->createMock(Schema::class));

        $this->middleware = new ValidateRelationshipCommand(
            $validators,
            $schemas,
            $this->errorFactory = $this->createMock(ResourceErrorFactory::class),
        );
    }

    /**
     * @return array<string,array<Closure>>
     */
    public static function commandProvider(): array
    {
        return [
            'update' => [
                function (ResourceType $type, Request $request = null): UpdateRelationshipCommand {
                    $operation = new UpdateToOne(
                        new Ref(type: $type, id: new ResourceId('123'), relationship: 'author'),
                        new ResourceIdentifier(new ResourceType('users'), new ResourceId('456')),
                    );

                    return new UpdateRelationshipCommand($request, $operation);
                },
            ],
            'attach' => [
                function (ResourceType $type, Request $request = null): AttachRelationshipCommand {
                    $operation = new UpdateToMany(
                        OpCodeEnum::Add,
                        new Ref(type: $type, id: new ResourceId('123'), relationship: 'tags'),
                        new ListOfResourceIdentifiers(),
                    );

                    return new AttachRelationshipCommand($request, $operation);
                },
            ],
        ];
    }

    /**
     * @param Closure(ResourceType, ?Request=): (UpdateRelationshipCommand|AttachRelationshipCommand) $factory
     * @return void
     * @dataProvider commandProvider
     */
    public function testItPassesValidation(Closure $factory): void
    {
        $command = $factory($this->type, $request = $this->createMock(Request::class));
        $command = $command->withModel($model = new \stdClass());
        $operation = $command->operation();

        $this->relationshipValidator
            ->expects($this->once())
            ->method('make')
            ->with($this->identicalTo($request), $this->identicalTo($model), $this->identicalTo($operation))
            ->willReturn($validator = $this->createMock(Validator::class));

        $this->relationshipValidator
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
            function (UpdateRelationshipCommand|AttachRelationshipCommand $cmd)
            use ($command, $validated, $expected): Result {
                $this->assertNotSame($command, $cmd);
                $this->assertSame($validated, $cmd->validated());
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
    }

    /**
     * @param Closure(ResourceType, ?Request=): (UpdateRelationshipCommand|AttachRelationshipCommand) $factory
     * @return void
     * @dataProvider commandProvider
     */
    public function testItFailsValidation(Closure $factory): void
    {
        $command = $factory($this->type);
        $command = $command->withModel($model = new \stdClass());
        $operation = $command->operation();

        $this->relationshipValidator
            ->expects($this->once())
            ->method('make')
            ->with(null, $this->identicalTo($model), $this->identicalTo($operation))
            ->willReturn($validator = $this->createMock(Validator::class));

        $this->relationshipValidator
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
     * @param Closure(ResourceType, ?Request=): (UpdateRelationshipCommand|AttachRelationshipCommand) $factory
     * @return void
     * @dataProvider commandProvider
     */
    public function testItSetsValidatedDataIfNotValidating(Closure $factory): void
    {
        $command = $factory($this->type);
        $command = $command->withModel($model = new \stdClass())->skipValidation();
        $operation = $command->operation();

        $this->relationshipValidator
            ->expects($this->once())
            ->method('extract')
            ->with($this->identicalTo($model), $this->identicalTo($operation))
            ->willReturn($validated = ['foo' => 'bar']);

        $this->relationshipValidator
            ->expects($this->never())
            ->method('make');

        $expected = Result::ok();

        $actual = $this->middleware->handle(
            $command,
            function (UpdateRelationshipCommand|AttachRelationshipCommand $cmd)
            use ($command, $validated, $expected): Result {
                $this->assertNotSame($command, $cmd);
                $this->assertSame($validated, $cmd->validated());
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
    }

    /**
     * @param Closure(ResourceType, ?Request=): (UpdateRelationshipCommand|AttachRelationshipCommand) $factory
     * @return void
     * @dataProvider commandProvider
     */
    public function testItDoesNotValidateIfAlreadyValidated(Closure $factory): void
    {
        $command = $factory($this->type);
        $command = $command
            ->withModel(new \stdClass())
            ->withValidated($validated = ['foo' => 'bar']);

        $this->relationshipValidator
            ->expects($this->never())
            ->method($this->anything());

        $expected = Result::ok();

        $actual = $this->middleware->handle(
            $command,
            function (UpdateRelationshipCommand|AttachRelationshipCommand $cmd)
            use ($command, $validated, $expected): Result {
                $this->assertSame($command, $cmd);
                $this->assertSame($validated, $cmd->validated());
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
    }
}
