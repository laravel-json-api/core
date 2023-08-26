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

namespace LaravelJsonApi\Core\Tests\Unit\Http\Actions\AttachRelationship\Middleware;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Auth\ResourceAuthorizer;
use LaravelJsonApi\Core\Auth\ResourceAuthorizerFactory;
use LaravelJsonApi\Core\Document\Input\Values\ResourceId;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;
use LaravelJsonApi\Core\Http\Actions\AttachRelationship\AttachRelationshipActionInput;
use LaravelJsonApi\Core\Http\Actions\AttachRelationship\Middleware\AuthorizeAttachRelationshipAction;
use LaravelJsonApi\Core\Responses\RelationshipResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AuthorizeAttachRelationshipActionTest extends TestCase
{
    /**
     * @var MockObject&ResourceAuthorizer
     */
    private ResourceAuthorizer&MockObject $authorizer;

    /**
     * @var AuthorizeAttachRelationshipAction
     */
    private AuthorizeAttachRelationshipAction $middleware;

    /**
     * @var AttachRelationshipActionInput
     */
    private AttachRelationshipActionInput $action;

    /**
     * @var Request
     */
    private Request $request;

    /**
     * @var \stdClass
     */
    private \stdClass $model;

    /**
     * @var string
     */
    private string $field;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = new AuthorizeAttachRelationshipAction(
            $factory = $this->createMock(ResourceAuthorizerFactory::class),
        );

        $this->action = (new AttachRelationshipActionInput(
            $this->request = $this->createMock(Request::class),
            $type = new ResourceType('posts'),
            new ResourceId('123'),
            $this->field = 'comments',
        ))->withModel($this->model = new \stdClass());

        $factory
            ->method('make')
            ->with($this->identicalTo($type))
            ->willReturn($this->authorizer = $this->createMock(ResourceAuthorizer::class));
    }

    /**
     * @return void
     */
    public function testItPassesAuthorization(): void
    {
        $this->authorizer
            ->expects($this->once())
            ->method('attachRelationshipOrFail')
            ->with($this->identicalTo($this->request), $this->identicalTo($this->model), $this->field);

        $expected = $this->createMock(RelationshipResponse::class);

        $actual = $this->middleware->handle(
            $this->action,
            function ($passed) use ($expected): RelationshipResponse {
                $this->assertSame($this->action, $passed);
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
    }

    /**
     * @return void
     */
    public function testItFailsAuthorization(): void
    {
        $this->authorizer
            ->expects($this->once())
            ->method('attachRelationshipOrFail')
            ->with($this->identicalTo($this->request), $this->identicalTo($this->model), $this->field)
            ->willThrowException($expected = new AuthorizationException());

        try {
            $this->middleware->handle(
                $this->action,
                fn() => $this->fail('Next middleware should not be called.'),
            );
            $this->fail('No exception thrown.');
        } catch (AuthorizationException $actual) {
            $this->assertSame($expected, $actual);
        }
    }
}
