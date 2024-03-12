<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Bus\Queries\FetchMany\Middleware;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Validation\Container as ValidatorContainer;
use LaravelJsonApi\Contracts\Validation\Factory;
use LaravelJsonApi\Contracts\Validation\QueryErrorFactory;
use LaravelJsonApi\Contracts\Validation\QueryManyValidator;
use LaravelJsonApi\Core\Bus\Queries\FetchMany\FetchManyQuery;
use LaravelJsonApi\Core\Bus\Queries\FetchMany\Middleware\ValidateFetchManyQuery;
use LaravelJsonApi\Core\Bus\Queries\Result;
use LaravelJsonApi\Core\Document\ErrorList;
use LaravelJsonApi\Core\Extensions\Atomic\Results\Result as Payload;
use LaravelJsonApi\Core\Query\Input\QueryMany;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ValidateFetchManyQueryTest extends TestCase
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
     * @var QueryErrorFactory&MockObject
     */
    private QueryErrorFactory&MockObject $errorFactory;

    /**
     * @var ValidateFetchManyQuery
     */
    private ValidateFetchManyQuery $middleware;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->type = new ResourceType('posts');

        $this->middleware = new ValidateFetchManyQuery(
            $this->validators = $this->createMock(ValidatorContainer::class),
            $this->errorFactory = $this->createMock(QueryErrorFactory::class),
        );
    }

    /**
     * @return void
     */
    public function testItPassesValidation(): void
    {
        $query = FetchManyQuery::make(
            $request = $this->createMock(Request::class),
            $input = new QueryMany($this->type, ['foo' => 'bar']),
        );

        $queryValidator = $this->willValidate($request);

        $queryValidator
            ->expects($this->once())
            ->method('make')
            ->with($this->identicalTo($input))
            ->willReturn($validator = $this->createMock(Validator::class));

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
            function (FetchManyQuery $passed) use ($query, $validated, $expected): Result {
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
    public function testItFailsValidation(): void
    {
        $query = FetchManyQuery::make(
            $request = $this->createMock(Request::class),
            $input = new QueryMany($this->type, ['foo' => 'bar']),
        );

        $queryValidator = $this->willValidate($request);

        $queryValidator
            ->expects($this->once())
            ->method('make')
            ->with($this->identicalTo($input))
            ->willReturn($validator = $this->createMock(Validator::class));

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

        $query = FetchManyQuery::make($request, new QueryMany($this->type, $params = ['foo' => 'bar']))
            ->skipValidation();

        $this->validators
            ->expects($this->never())
            ->method($this->anything());

        $expected = Result::ok(new Payload(null, true));

        $actual = $this->middleware->handle(
            $query,
            function (FetchManyQuery $passed) use ($query, $params, $expected): Result {
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

        $query = FetchManyQuery::make($request, new QueryMany($this->type, ['blah' => 'blah']),)
            ->withValidated($validated = ['foo' => 'bar']);

        $this->validators
            ->expects($this->never())
            ->method($this->anything());

        $expected = Result::ok(new Payload(null, false));

        $actual = $this->middleware->handle(
            $query,
            function (FetchManyQuery $passed) use ($query, $validated, $expected): Result {
                $this->assertSame($query, $passed);
                $this->assertSame($validated, $passed->validated());
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
    }

    /**
     * @param Request|null $request
     * @return QueryManyValidator&MockObject
     */
    private function willValidate(?Request $request): QueryManyValidator&MockObject
    {
        $this->validators
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
            ->method('queryMany')
            ->willReturn($validator = $this->createMock(QueryManyValidator::class));

        return $validator;
    }
}
