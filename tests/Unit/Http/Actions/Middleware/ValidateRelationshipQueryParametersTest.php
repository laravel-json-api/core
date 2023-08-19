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

namespace LaravelJsonApi\Core\Tests\Unit\Http\Actions\Middleware;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Schema\Container as SchemaContainer;
use LaravelJsonApi\Contracts\Schema\Relation;
use LaravelJsonApi\Contracts\Schema\Schema;
use LaravelJsonApi\Contracts\Validation\Container as ValidatorContainer;
use LaravelJsonApi\Contracts\Validation\Factory as ValidatorFactory;
use LaravelJsonApi\Contracts\Validation\QueryErrorFactory;
use LaravelJsonApi\Contracts\Validation\QueryManyValidator;
use LaravelJsonApi\Contracts\Validation\QueryOneValidator;
use LaravelJsonApi\Core\Document\ErrorList;
use LaravelJsonApi\Core\Document\Input\Values\ResourceId;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Core\Http\Actions\Input\ActionInput;
use LaravelJsonApi\Core\Http\Actions\Input\IsRelatable;
use LaravelJsonApi\Core\Http\Actions\Middleware\ValidateRelationshipQueryParameters;
use LaravelJsonApi\Core\Http\Actions\UpdateRelationship\UpdateRelationshipActionInput;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ValidateRelationshipQueryParametersTest extends TestCase
{
    /**
     * @var ResourceType
     */
    private ResourceType $type;

    /**
     * @var Request
     */
    private Request $request;

    /**
     * @var ErrorList
     */
    private ErrorList $errors;

    /**
     * @var Schema&MockObject
     */
    private Schema&MockObject $schema;

    /**
     * @var ValidatorFactory&MockObject
     */
    private ValidatorFactory&MockObject $validatorFactory;

    /**
     * @var MockObject&QueryErrorFactory
     */
    private QueryErrorFactory&MockObject $errorFactory;

    /**
     * @var ValidateRelationshipQueryParameters
     */
    private ValidateRelationshipQueryParameters $middleware;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->type = new ResourceType('videos');
        $this->request = $this->createMock(Request::class);
        $this->errors = new ErrorList();

        $schemas = $this->createMock(SchemaContainer::class);
        $schemas
            ->expects($this->once())
            ->method('schemaFor')
            ->with($this->identicalTo($this->type))
            ->willReturn($this->schema = $this->createMock(Schema::class));

        $validators = $this->createMock(ValidatorContainer::class);
        $validators
            ->expects($this->once())
            ->method('validatorsFor')
            ->with($this->identicalTo($this->type))
            ->willReturn($this->validatorFactory = $this->createMock(ValidatorFactory::class));

        $this->middleware = new ValidateRelationshipQueryParameters(
            $schemas,
            $validators,
            $this->errorFactory = $this->createMock(QueryErrorFactory::class),
        );
    }

    /**
     * @return void
     */
    public function testItValidatesToOneAndPasses(): void
    {
        $action = new UpdateRelationshipActionInput(
            $this->request,
            $this->type,
            new ResourceId('1'),
            'author',
        );

        $this->withRelation('author', true);
        $this->willValidateToOne($validated = ['include' => 'profile']);

        $expected = $this->createMock(Responsable::class);

        $actual = $this->middleware->handle(
            $action,
            function (ActionInput&IsRelatable $passed) use ($action, $validated, $expected): Responsable {
                $this->assertNotSame($action, $passed);
                $this->assertSame($validated, $passed->query()->toQuery());
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
    }

    /**
     * @return void
     */
    public function testItValidatesToOneAndFails(): void
    {
        $action = new UpdateRelationshipActionInput(
            $this->request,
            $this->type,
            new ResourceId('1'),
            'author',
        );

        $this->withRelation('author', true);
        $this->willValidateToOne(null);

        try {
            $this->middleware->handle(
                $action,
                fn() => $this->fail('Not expecting next middleware to be called.'),
            );
            $this->fail('No exception thrown.');
        } catch (JsonApiException $ex) {
            $this->assertSame($this->errors, $ex->getErrors());
        }
    }

    /**
     * @return void
     */
    public function testItValidatesToManyAndPasses(): void
    {
        $action = new UpdateRelationshipActionInput(
            $this->request,
            $this->type,
            new ResourceId('1'),
            'tags',
        );

        $this->withRelation('tags', false);
        $this->willValidateToMany($validated = ['include' => 'profile']);

        $expected = $this->createMock(Responsable::class);

        $actual = $this->middleware->handle(
            $action,
            function (ActionInput&IsRelatable $passed) use ($action, $validated, $expected): Responsable {
                $this->assertNotSame($action, $passed);
                $this->assertSame($validated, $passed->query()->toQuery());
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
    }

    /**
     * @return void
     */
    public function testItValidatesToManyAndFails(): void
    {
        $action = new UpdateRelationshipActionInput(
            $this->request,
            $this->type,
            new ResourceId('1'),
            'tags',
        );

        $this->withRelation('tags', false);
        $this->willValidateToMany(null);

        try {
            $this->middleware->handle(
                $action,
                fn() => $this->fail('Not expecting next middleware to be called.'),
            );
            $this->fail('No exception thrown.');
        } catch (JsonApiException $ex) {
            $this->assertSame($this->errors, $ex->getErrors());
        }
    }

    /**
     * @param string $fieldName
     * @param bool $toOne
     * @return void
     */
    private function withRelation(string $fieldName, bool $toOne): void
    {
        $this->schema
            ->expects($this->once())
            ->method('relationship')
            ->with($fieldName)
            ->willReturn($relation = $this->createMock(Relation::class));

        $relation->method('toOne')->willReturn($toOne);
        $relation->method('toMany')->willReturn(!$toOne);
    }

    /**
     * @param array|null $validated
     * @return void
     */
    private function willValidateToOne(?array $validated): void
    {
        $this->validatorFactory
            ->expects($this->once())
            ->method('queryOne')
            ->willReturn($queryOneValidator = $this->createMock(QueryOneValidator::class));

        $this->validatorFactory
            ->expects($this->never())
            ->method('queryMany');

        $queryOneValidator
            ->expects($this->once())
            ->method('forRequest')
            ->with($this->identicalTo($this->request))
            ->willReturn($this->withValidator($validated));
    }

    /**
     * @param array|null $validated
     * @return void
     */
    private function willValidateToMany(?array $validated): void
    {
        $this->validatorFactory
            ->expects($this->once())
            ->method('queryMany')
            ->willReturn($queryOneValidator = $this->createMock(QueryManyValidator::class));

        $this->validatorFactory
            ->expects($this->never())
            ->method('queryOne');

        $queryOneValidator
            ->expects($this->once())
            ->method('forRequest')
            ->with($this->identicalTo($this->request))
            ->willReturn($this->withValidator($validated));
    }

    /**
     * @param array|null $validated
     * @return Validator&MockObject
     */
    private function withValidator(?array $validated): Validator&MockObject
    {
        $fails = ($validated === null);
        $validator = $this->createMock(Validator::class);

        $validator
            ->method('fails')
            ->willReturn($fails);

        if ($fails) {
            $validator
                ->expects($this->never())
                ->method('validated');

            $this->errorFactory
                ->expects($this->once())
                ->method('make')
                ->with($this->identicalTo($validator))
                ->willReturn($this->errors);
            return $validator;
        }

        $validator
            ->method('validated')
            ->willReturn($validated);

        $this->errorFactory
            ->expects($this->never())
            ->method('make');

        return $validator;
    }
}