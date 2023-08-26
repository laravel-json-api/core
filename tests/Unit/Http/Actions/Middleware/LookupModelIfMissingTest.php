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

use LaravelJsonApi\Contracts\Store\Store;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Core\Http\Actions\Middleware\LookupModelIfMissing;
use LaravelJsonApi\Core\Http\Actions\Update\UpdateActionInput;
use LaravelJsonApi\Core\Responses\DataResponse;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LookupModelIfMissingTest extends TestCase
{
    /**
     * @var MockObject&Store
     */
    private Store&MockObject $store;

    /**
     * @var LookupModelIfMissing
     */
    private LookupModelIfMissing $middleware;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = new LookupModelIfMissing(
            $this->store = $this->createMock(Store::class),
        );
    }

    /**
     * @return void
     */
    public function testItLooksUpModel(): void
    {
        $action = $this->createMock(UpdateActionInput::class);
        $action->method('model')->willReturn(null);
        $action->method('type')->willReturn($type = new ResourceType('posts'));
        $action->method('id')->willReturn($id = new ResourceId('123'));
        $action
            ->expects($this->once())
            ->method('withModel')
            ->with($model = new \stdClass())
            ->willReturn($passed = $this->createMock(UpdateActionInput::class));

        $this->store
            ->expects($this->once())
            ->method('find')
            ->with($this->identicalTo($type), $this->identicalTo($id))
            ->willReturn($model);

        $expected = new DataResponse(null);

        $actual = $this->middleware->handle(
            $action,
            function (UpdateActionInput $input) use ($passed, $expected): DataResponse {
                $this->assertSame($input, $passed);
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
    }

    /**
     * @return void
     */
    public function testItThrowsIfModelDoesNotExist(): void
    {
        $action = $this->createMock(UpdateActionInput::class);
        $action->method('model')->willReturn(null);
        $action->method('type')->willReturn($type = new ResourceType('posts'));
        $action->method('id')->willReturn($id = new ResourceId('123'));
        $action->expects($this->never())->method('withModel');

        $this->store
            ->expects($this->once())
            ->method('find')
            ->with($this->identicalTo($type), $this->identicalTo($id))
            ->willReturn(null);

        try {
            $this->middleware->handle(
                $action,
                fn () => $this->fail('Not expecting next closure to be called.'),
            );
            $this->fail('No exception thrown.');
        } catch (JsonApiException $ex) {
            $this->assertSame(404, $ex->getStatusCode());
        }
    }

    /**
     * @return void
     */
    public function testItDoesNotFindModel(): void
    {
        $action = $this->createMock(UpdateActionInput::class);
        $action->method('model')->willReturn(new \stdClass());
        $action->expects($this->never())->method('withModel');

        $this->store->expects($this->never())->method('find');

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
