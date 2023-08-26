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

namespace LaravelJsonApi\Core\Tests\Unit\Auth;

use Illuminate\Contracts\Container\Container;
use LaravelJsonApi\Contracts\Auth\Authorizer as AuthorizerContract;
use LaravelJsonApi\Contracts\Schema\Container as SchemaContainer;
use LaravelJsonApi\Core\Auth\Authorizer;
use LaravelJsonApi\Core\Auth\AuthorizerResolver;
use LaravelJsonApi\Core\Auth\Container as AuthContainer;
use LaravelJsonApi\Core\Support\ContainerResolver;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    /**
     * @var Container&MockObject
     */
    private Container&MockObject $serviceContainer;

    /**
     * @var MockObject&SchemaContainer
     */
    private SchemaContainer&MockObject $schemaContainer;

    /**
     * @var AuthContainer
     */
    private AuthContainer $authContainer;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->serviceContainer = $this->createMock(Container::class);

        $this->authContainer = new AuthContainer(
            new ContainerResolver(fn () => $this->serviceContainer),
            $this->schemaContainer = $this->createMock(SchemaContainer::class),
        );
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        AuthorizerResolver::reset();
        AuthContainer::guessUsing(null);

        parent::tearDown();
    }

    /**
     * @return void
     */
    public function testItUsesDefaultAuthorizer(): void
    {
        $this->schemaContainer
            ->expects($this->once())
            ->method('schemaClassFor')
            ->with($this->identicalTo($type = new ResourceType('comments')))
            ->willReturn('App\JsonApi\V1\Comments\CommentSchema');

        $this->serviceContainer
            ->expects($this->once())
            ->method('make')
            ->with(Authorizer::class)
            ->willReturn($expected = $this->createMock(AuthorizerContract::class));

        $actual = $this->authContainer->authorizerFor($type);

        $this->assertSame($expected, $actual);
    }

    /**
     * @return void
     */
    public function testItUsesCustomDefaultAuthorizer(): void
    {
        AuthorizerResolver::useDefault(TestAuthorizer::class);

        $this->schemaContainer
            ->expects($this->once())
            ->method('schemaClassFor')
            ->with($this->identicalTo($type = new ResourceType('comments')))
            ->willReturn('App\JsonApi\V1\Comments\CommentSchema');

        $this->serviceContainer
            ->expects($this->once())
            ->method('make')
            ->with(TestAuthorizer::class)
            ->willReturn($expected = $this->createMock(AuthorizerContract::class));

        $actual = $this->authContainer->authorizerFor($type);

        $this->assertSame($expected, $actual);
    }

    /**
     * @return void
     */
    public function testItUsesAuthorizerInSameNamespaceAsSchema(): void
    {
        $this->schemaContainer
            ->expects($this->once())
            ->method('schemaClassFor')
            ->with($this->identicalTo($type = new ResourceType('comments')))
            ->willReturn('LaravelJsonApi\Core\Tests\Unit\Auth\TestSchema');

        $this->serviceContainer
            ->expects($this->once())
            ->method('make')
            ->with(TestAuthorizer::class)
            ->willReturn($expected = $this->createMock(AuthorizerContract::class));

        $actual = $this->authContainer->authorizerFor($type);

        $this->assertSame($expected, $actual);
    }

    /**
     * @return void
     */
    public function testItUsesRegisteredAuthorizer(): void
    {
        $schemaClass = 'App\JsonApi\V1\Comments\CommentSchema';

        AuthorizerResolver::register($schemaClass, TestAuthorizer::class);

        $this->schemaContainer
            ->expects($this->once())
            ->method('schemaClassFor')
            ->with($this->identicalTo($type = new ResourceType('comments')))
            ->willReturn($schemaClass);

        $this->serviceContainer
            ->expects($this->once())
            ->method('make')
            ->with(TestAuthorizer::class)
            ->willReturn($expected = $this->createMock(AuthorizerContract::class));

        $actual = $this->authContainer->authorizerFor($type);

        $this->assertSame($expected, $actual);
    }
}
