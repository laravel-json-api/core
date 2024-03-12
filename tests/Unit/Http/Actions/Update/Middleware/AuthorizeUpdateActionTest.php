<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Http\Actions\Update\Middleware;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Auth\ResourceAuthorizer;
use LaravelJsonApi\Core\Auth\ResourceAuthorizerFactory;
use LaravelJsonApi\Core\Http\Actions\Update\Middleware\AuthorizeUpdateAction;
use LaravelJsonApi\Core\Http\Actions\Update\UpdateActionInput;
use LaravelJsonApi\Core\Responses\DataResponse;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AuthorizeUpdateActionTest extends TestCase
{
    /**
     * @var MockObject&ResourceAuthorizer
     */
    private ResourceAuthorizer&MockObject $authorizer;

    /**
     * @var AuthorizeUpdateAction
     */
    private AuthorizeUpdateAction $middleware;

    /**
     * @var UpdateActionInput
     */
    private UpdateActionInput $action;

    /**
     * @var Request
     */
    private Request $request;

    /**
     * @var \stdClass
     */
    private \stdClass $model;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = new AuthorizeUpdateAction(
            $factory = $this->createMock(ResourceAuthorizerFactory::class),
        );

        $this->action = (new UpdateActionInput(
            $this->request = $this->createMock(Request::class),
            $type = new ResourceType('posts'),
            new ResourceId('123'),
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
            ->method('updateOrFail')
            ->with($this->identicalTo($this->request), $this->identicalTo($this->model));

        $expected = new DataResponse(null);

        $actual = $this->middleware->handle($this->action, function ($passed) use ($expected): DataResponse {
            $this->assertSame($this->action, $passed);
            return $expected;
        });

        $this->assertSame($expected, $actual);
    }

    /**
     * @return void
     */
    public function testItFailsAuthorization(): void
    {
        $this->authorizer
            ->expects($this->once())
            ->method('updateOrFail')
            ->with($this->identicalTo($this->request), $this->identicalTo($this->model))
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
