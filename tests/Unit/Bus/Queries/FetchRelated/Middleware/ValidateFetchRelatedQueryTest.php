<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Bus\Queries\FetchRelated\Middleware;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Schema\Container as SchemaContainer;
use LaravelJsonApi\Contracts\Schema\Relation;
use LaravelJsonApi\Contracts\Schema\Schema;
use LaravelJsonApi\Contracts\Validation\Container as ValidatorContainer;
use LaravelJsonApi\Contracts\Validation\Factory;
use LaravelJsonApi\Contracts\Validation\QueryErrorFactory;
use LaravelJsonApi\Contracts\Validation\QueryManyValidator;
use LaravelJsonApi\Contracts\Validation\QueryOneValidator;
use LaravelJsonApi\Core\Bus\Queries\FetchRelated\FetchRelatedQuery;
use LaravelJsonApi\Core\Bus\Queries\FetchRelated\Middleware\ValidateFetchRelatedQuery;
use LaravelJsonApi\Core\Bus\Queries\Result;
use LaravelJsonApi\Core\Document\ErrorList;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result as Payload;
use LaravelJsonApi\Core\Query\Input\QueryRelated;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ValidateFetchRelatedQueryTest extends TestCase
{
    /**
     * @var ResourceType
     */
    private ResourceType $type;

    /**
     * @var MockObject&SchemaContainer
     */
    private SchemaContainer&MockObject $schemas;

    /**
     * @var ValidatorContainer&MockObject
     */
    private ValidatorContainer&MockObject $validators;

    /**
     * @var QueryErrorFactory&MockObject
     */
    private QueryErrorFactory&MockObject $errorFactory;

    /**
     * @var ValidateFetchRelatedQuery
     */
    private ValidateFetchRelatedQuery $middleware;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->type = new ResourceType('posts');

        $this->middleware = new ValidateFetchRelatedQuery(
            $this->schemas = $this->createMock(SchemaContainer::class),
            $this->validators = $this->createMock(ValidatorContainer::class),
            $this->errorFactory = $this->createMock(QueryErrorFactory::class),
        );
    }

    /**
     * @return void
     */
    public function testItPassesToOneValidation(): void
    {
        $request = $this->createMock(Request::class);
        $query = FetchRelatedQuery::make($request, $input = new QueryRelated(
            $this->type,
            new ResourceId('123'),
            $fieldName = 'author',
            ['foo' => 'bar'],
        ));

        $validator = $this->willValidateToOne($fieldName, $request, $input);

        $validator
            ->expects($this->once())
            ->method('fails')
            ->willReturn(false);

        $validator
            ->expects($this->once())
            ->method('validated')
            ->willReturn($validated = ['baz' => 'bat']);

        $expected = Result::ok(
            new Payload(null, true),
        );

        $actual = $this->middleware->handle(
            $query,
            function (FetchRelatedQuery $passed) use ($query, $validated, $expected): Result {
                $this->assertNotSame($query, $passed);
                $this->assertSame($validated, $passed->validated());
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
    }

    /**
     * @return void
     */
    public function testItFailsToOneValidation(): void
    {
        $request = $this->createMock(Request::class);
        $query = FetchRelatedQuery::make($request, $input = new QueryRelated(
            $this->type,
            new ResourceId('456'),
            $fieldName = 'image',
            ['foo' => 'bar'],
        ));

        $validator = $this->willValidateToOne($fieldName, $request, $input);

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
            $query,
            fn() => $this->fail('Not expecting next middleware to be called.'),
        );

        $this->assertTrue($actual->didFail());
        $this->assertSame($errors, $actual->errors());
    }

    /**
     * @return void
     */
    public function testItPassesToManyValidation(): void
    {
        $request = $this->createMock(Request::class);
        $query = FetchRelatedQuery::make($request, $input = new QueryRelated(
            $this->type,
            new ResourceId('123'),
            $fieldName = 'comments',
            ['foo' => 'bar'],
        ));

        $validator = $this->willValidateToMany($fieldName, $request, $input);

        $validator
            ->expects($this->once())
            ->method('fails')
            ->willReturn(false);

        $validator
            ->expects($this->once())
            ->method('validated')
            ->willReturn($validated = ['baz' => 'bat']);

        $expected = Result::ok(
            new Payload(null, true),
        );

        $actual = $this->middleware->handle(
            $query,
            function (FetchRelatedQuery $passed) use ($query, $validated, $expected): Result {
                $this->assertNotSame($query, $passed);
                $this->assertSame($validated, $passed->validated());
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
    }

    /**
     * @return void
     */
    public function testItFailsToManyValidation(): void
    {
        $request = $this->createMock(Request::class);
        $query = FetchRelatedQuery::make($request, $input = new QueryRelated(
            $this->type,
            new ResourceId('123'),
            $fieldName = 'tags',
            ['foo' => 'bar'],
        ));

        $validator = $this->willValidateToMany($fieldName, $request, $input);

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
            $query,
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
        $request = $this->createMock(Request::class);

        $query = FetchRelatedQuery::make($request, $input = new QueryRelated(
            $this->type,
            new ResourceId('123'),
            'comments',
            $params = ['foo' => 'bar'],
        ))->skipValidation();

        $this->willNotValidate();

        $expected = Result::ok(new Payload(null, true));

        $actual = $this->middleware->handle(
            $query,
            function (FetchRelatedQuery $passed) use ($query, $params, $expected): Result {
                $this->assertNotSame($query, $passed);
                $this->assertSame($params, $passed->validated());
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
        $request = $this->createMock(Request::class);

        $query = FetchRelatedQuery::make($request, $input = new QueryRelated(
            $this->type,
            new ResourceId('123'),
            'author',
            ['blah' => 'blah'],
        ))->withValidated($validated = ['foo' => 'bar']);

        $this->willNotValidate();

        $expected = Result::ok(new Payload(null, false));

        $actual = $this->middleware->handle(
            $query,
            function (FetchRelatedQuery $passed) use ($query, $validated, $expected): Result {
                $this->assertSame($query, $passed);
                $this->assertSame($validated, $passed->validated());
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
    }

    /**
     * @param string $fieldName
     * @param Request|null $request
     * @param QueryRelated $input
     * @return Validator&MockObject
     */
    private function willValidateToOne(string $fieldName, ?Request $request, QueryRelated $input): Validator&MockObject
    {
        $factory = $this->willValidateField($fieldName, true, $request);


        $factory
            ->expects($this->once())
            ->method('queryOne')
            ->willReturn($queryOneValidator = $this->createMock(QueryOneValidator::class));

        $factory
            ->expects($this->never())
            ->method('queryMany');

        $queryOneValidator
            ->expects($this->once())
            ->method('make')
            ->with($this->identicalTo($input))
            ->willReturn($validator = $this->createMock(Validator::class));

        return $validator;
    }

    /**
     * @param string $fieldName
     * @param Request|null $request
     * @param QueryRelated $input
     * @return Validator&MockObject
     */
    private function willValidateToMany(string $fieldName, ?Request $request, QueryRelated $input): Validator&MockObject
    {
        $factory = $this->willValidateField($fieldName, false, $request);

        $factory
            ->expects($this->once())
            ->method('queryMany')
            ->willReturn($queryOneValidator = $this->createMock(QueryManyValidator::class));

        $factory
            ->expects($this->never())
            ->method('queryOne');

        $queryOneValidator
            ->expects($this->once())
            ->method('make')
            ->with($this->identicalTo($input))
            ->willReturn($validator = $this->createMock(Validator::class));

        return $validator;
    }

    /**
     * @param string $fieldName
     * @param bool $toOne
     * @param Request|null $request
     * @return MockObject&Factory
     */
    private function willValidateField(string $fieldName, bool $toOne, ?Request $request): Factory&MockObject
    {
        $this->schemas
            ->expects($this->once())
            ->method('schemaFor')
            ->with($this->identicalTo($this->type))
            ->willReturn($schema = $this->createMock(Schema::class));

        $schema
            ->expects($this->once())
            ->method('relationship')
            ->with($this->identicalTo($fieldName))
            ->willReturn($relation = $this->createMock(Relation::class));

        $relation
            ->expects($this->once())
            ->method('inverse')
            ->willReturn($inverse = 'tags');

        $relation->method('toOne')->willReturn($toOne);
        $relation->method('toMany')->willReturn(!$toOne);

        $this->validators
            ->expects($this->once())
            ->method('validatorsFor')
            ->with($this->identicalTo($inverse))
            ->willReturn($factory = $this->createMock(Factory::class));

        $factory
            ->expects($this->once())
            ->method('withRequest')
            ->with($this->identicalTo($request))
            ->willReturnSelf();

        return $factory;
    }

    /**
     * @return void
     */
    private function willNotValidate(): void
    {
        $this->schemas
            ->expects($this->never())
            ->method($this->anything());

        $this->validators
            ->expects($this->never())
            ->method($this->anything());

        $this->errorFactory
            ->expects($this->never())
            ->method($this->anything());
    }
}
