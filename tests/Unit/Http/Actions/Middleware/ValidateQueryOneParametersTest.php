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

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Validation\Container;
use LaravelJsonApi\Contracts\Validation\Factory;
use LaravelJsonApi\Contracts\Validation\QueryErrorFactory;
use LaravelJsonApi\Contracts\Validation\QueryOneValidator;
use LaravelJsonApi\Core\Document\ErrorList;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Core\Http\Actions\Middleware\ValidateQueryOneParameters;
use LaravelJsonApi\Core\Http\Actions\Store\StoreActionInput;
use LaravelJsonApi\Core\Responses\DataResponse;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ValidateQueryOneParametersTest extends TestCase
{
    /**
     * @var Validator&MockObject
     */
    private Validator&MockObject $validator;

    /**
     * @var MockObject&QueryErrorFactory
     */
    private QueryErrorFactory&MockObject $errorFactory;

    /**
     * @var ValidateQueryOneParameters
     */
    private ValidateQueryOneParameters $middleware;

    /**
     * @var StoreActionInput
     */
    private StoreActionInput $action;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = $this->createMock(Validator::class);

        $this->middleware = new ValidateQueryOneParameters(
            $container = $this->createMock(Container::class),
            $this->errorFactory = $this->createMock(QueryErrorFactory::class),
        );

        $this->action = new StoreActionInput(
            $request = $this->createMock(Request::class),
            $type = new ResourceType('videos'),
        );

        $container
            ->expects($this->once())
            ->method('validatorsFor')
            ->with($this->identicalTo($type))
            ->willReturn($factory = $this->createMock(Factory::class));

        $factory
            ->expects($this->once())
            ->method('queryOne')
            ->willReturn($queryOneValidator = $this->createMock(QueryOneValidator::class));

        $queryOneValidator
            ->expects($this->once())
            ->method('forRequest')
            ->with($this->identicalTo($request))
            ->willReturn($this->validator);
    }

    /**
     * @return void
     */
    public function testItPasses(): void
    {
        $this->validator
            ->method('fails')
            ->willReturn(false);

        $this->validator
            ->method('validated')
            ->willReturn($validated = ['include' => 'author']);

        $this->errorFactory
            ->expects($this->never())
            ->method($this->anything());

        $expected = new DataResponse(null);

        $actual = $this->middleware->handle(
            $this->action,
            function (StoreActionInput $passed) use ($validated, $expected): DataResponse {
                $this->assertNotSame($this->action, $passed);
                $this->assertSame($validated, $passed->query()->toQuery());
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
    }

    /**
     * @return void
     */
    public function testItFails(): void
    {
        $this->validator
            ->method('fails')
            ->willReturn(true);

        $this->errorFactory
            ->expects($this->once())
            ->method('make')
            ->with($this->identicalTo($this->validator))
            ->willReturn($expected = new ErrorList());

        try {
            $this->middleware->handle(
                $this->action,
                fn() => $this->fail('Not expecting next middleware to be called.'),
            );
            $this->fail('No exception thrown.');
        } catch (JsonApiException $ex) {
            $this->assertSame($expected, $ex->getErrors());
        }
    }
}
