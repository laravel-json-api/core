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

use LaravelJsonApi\Contracts\Resources\Container;
use LaravelJsonApi\Core\Document\Input\Values\ResourceId;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;
use LaravelJsonApi\Core\Http\Actions\Middleware\LookupResourceIdIfNotSet;
use LaravelJsonApi\Core\Http\Actions\Update\UpdateActionInput;
use LaravelJsonApi\Core\Responses\DataResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LookupResourceIdIfNotSetTest extends TestCase
{
    /**
     * @var MockObject&Container
     */
    private readonly Container&MockObject $resources;

    /**
     * @var LookupResourceIdIfNotSet
     */
    private readonly LookupResourceIdIfNotSet $middleware;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new LookupResourceIdIfNotSet(
            $this->resources = $this->createMock(Container::class),
        );
    }

    /**
     * @return void
     */
    public function testItLooksUpId(): void
    {
        $action = $this->createMock(UpdateActionInput::class);
        $action->method('type')->willReturn($type = new ResourceType('posts'));
        $action->method('modelOrFail')->willReturn($model = new \stdClass());
        $action->method('id')->willReturn(null);
        $action
            ->expects($this->once())
            ->method('withId')
            ->with($this->identicalTo($id = new ResourceId('123')))
            ->willReturn($passed = $this->createMock(UpdateActionInput::class));

        $this->resources
            ->expects($this->once())
            ->method('idForType')
            ->with($this->identicalTo($type), $this->identicalTo($model))
            ->willReturn($id);

        $expected = new DataResponse(null);

        $actual = $this->middleware->handle(
            $action,
            function (UpdateActionInput $input) use ($passed, $expected): DataResponse {
                $this->assertSame($passed, $input);
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
    }

    /**
     * @return void
     */
    public function testItDoesNotLookupId(): void
    {
        $action = $this->createMock(UpdateActionInput::class);
        $action->method('id')->willReturn(new ResourceId('123'));
        $action->expects($this->never())->method('withId');

        $this->resources
            ->expects($this->never())
            ->method('idForType');

        $expected = new DataResponse(null);

        $actual = $this->middleware->handle(
            $action,
            function (UpdateActionInput $input) use ($action, $expected): DataResponse {
                $this->assertSame($action, $input);
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
    }
}