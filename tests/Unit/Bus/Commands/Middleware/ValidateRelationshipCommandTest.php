<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
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
use LaravelJsonApi\Core\Bus\Commands\Command\Command;
use LaravelJsonApi\Core\Bus\Commands\Command\IsRelatable;
use LaravelJsonApi\Core\Bus\Commands\DetachRelationship\DetachRelationshipCommand;
use LaravelJsonApi\Core\Bus\Commands\Middleware\ValidateRelationshipCommand;
use LaravelJsonApi\Core\Bus\Commands\Result;
use LaravelJsonApi\Core\Bus\Commands\UpdateRelationship\UpdateRelationshipCommand;
use LaravelJsonApi\Core\Document\ErrorList;
use LaravelJsonApi\Core\Document\Input\Values\ListOfResourceIdentifiers;
use LaravelJsonApi\Core\Document\Input\Values\ResourceIdentifier;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\UpdateToMany;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\UpdateToOne;
use LaravelJsonApi\Core\Extensions\Atomic\Values\OpCodeEnum;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Ref;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ValidateRelationshipCommandTest extends TestCase
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

        $schemas = $this->createMock(SchemaContainer::class);
        $schemas
            ->method('schemaFor')
            ->with($this->identicalTo($this->type))
            ->willReturn($this->schema = $this->createMock(Schema::class));

        $this->middleware = new ValidateRelationshipCommand(
            $this->validators = $this->createMock(ValidatorContainer::class),
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
                function (ResourceType $type, ?Request $request = null): UpdateRelationshipCommand {
                    $operation = new UpdateToOne(
                        new Ref(type: $type, id: new ResourceId('123'), relationship: 'author'),
                        new ResourceIdentifier(new ResourceType('users'), new ResourceId('456')),
                    );

                    return new UpdateRelationshipCommand($request, $operation);
                },
            ],
            'attach' => [
                function (ResourceType $type, ?Request $request = null): AttachRelationshipCommand {
                    $operation = new UpdateToMany(
                        OpCodeEnum::Add,
                        new Ref(type: $type, id: new ResourceId('123'), relationship: 'tags'),
                        new ListOfResourceIdentifiers(),
                    );

                    return new AttachRelationshipCommand($request, $operation);
                },
            ],
            'detach' => [
                function (ResourceType $type, ?Request $request = null): DetachRelationshipCommand {
                    $operation = new UpdateToMany(
                        OpCodeEnum::Remove,
                        new Ref(type: $type, id: new ResourceId('123'), relationship: 'tags'),
                        new ListOfResourceIdentifiers(),
                    );

                    return new DetachRelationshipCommand($request, $operation);
                },
            ],
        ];
    }

    /**
     * @param Closure(ResourceType, ?Request=): (Command&IsRelatable) $factory
     * @return void
     * @dataProvider commandProvider
     */
    public function testItPassesValidation(Closure $factory): void
    {
        $command = $factory($this->type, $request = $this->createMock(Request::class));
        $command = $command->withModel($model = new \stdClass());
        $operation = $command->operation();

        $relationshipValidator = $this->willValidate($request);

        $relationshipValidator
            ->expects($this->once())
            ->method('make')
            ->with($this->identicalTo($operation), $this->identicalTo($model))
            ->willReturn($validator = $this->createMock(Validator::class));

        $relationshipValidator
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
            function (Command&IsRelatable $cmd) use ($command, $validated, $expected): Result {
                $this->assertNotSame($command, $cmd);
                $this->assertSame($validated, $cmd->validated());
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
    }

    /**
     * @param Closure(ResourceType, ?Request=): (Command&IsRelatable) $factory
     * @return void
     * @dataProvider commandProvider
     */
    public function testItFailsValidation(Closure $factory): void
    {
        $command = $factory($this->type);
        $command = $command->withModel($model = new \stdClass());
        $operation = $command->operation();

        $relationshipValidator = $this->willValidate(null);

        $relationshipValidator
            ->expects($this->once())
            ->method('make')
            ->with($this->identicalTo($operation), $this->identicalTo($model))
            ->willReturn($validator = $this->createMock(Validator::class));

        $relationshipValidator
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
     * @param Closure(ResourceType, ?Request=): (Command&IsRelatable) $factory
     * @return void
     * @dataProvider commandProvider
     */
    public function testItSetsValidatedDataIfNotValidating(Closure $factory): void
    {
        $command = $factory($this->type, $request = $this->createMock(Request::class));
        $command = $command->withModel($model = new \stdClass())->skipValidation();
        $operation = $command->operation();

        $relationshipValidator = $this->willValidate($request);

        $relationshipValidator
            ->expects($this->once())
            ->method('extract')
            ->with($this->identicalTo($operation), $this->identicalTo($model))
            ->willReturn($validated = ['foo' => 'bar']);

        $relationshipValidator
            ->expects($this->never())
            ->method('make');

        $expected = Result::ok();

        $actual = $this->middleware->handle(
            $command,
            function (Command&IsRelatable $cmd) use ($command, $validated, $expected): Result {
                $this->assertNotSame($command, $cmd);
                $this->assertSame($validated, $cmd->validated());
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
    }

    /**
     * @param Closure(ResourceType, ?Request=): (Command&IsRelatable) $factory
     * @return void
     * @dataProvider commandProvider
     */
    public function testItDoesNotValidateIfAlreadyValidated(Closure $factory): void
    {
        $command = $factory($this->type);
        $command = $command
            ->withModel(new \stdClass())
            ->withValidated($validated = ['foo' => 'bar']);

        $this->validators
            ->expects($this->never())
            ->method($this->anything());

        $expected = Result::ok();

        $actual = $this->middleware->handle(
            $command,
            function (Command&IsRelatable $cmd) use ($command, $validated, $expected): Result {
                $this->assertSame($command, $cmd);
                $this->assertSame($validated, $cmd->validated());
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
    }

    /**
     * @param Request|null $request
     * @return MockObject&RelationshipValidator
     */
    private function willValidate(?Request $request): RelationshipValidator&MockObject
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
            ->method('relation')
            ->willReturn($relationshipValidator = $this->createMock(RelationshipValidator::class));

        return $relationshipValidator;
    }
}
