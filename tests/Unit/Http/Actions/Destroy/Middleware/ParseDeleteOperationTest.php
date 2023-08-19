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

namespace LaravelJsonApi\Core\Tests\Unit\Http\Actions\Destroy\Middleware;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Document\Input\Values\ResourceId;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Ref;
use LaravelJsonApi\Core\Http\Actions\Destroy\DestroyActionInput;
use LaravelJsonApi\Core\Http\Actions\Destroy\Middleware\ParseDeleteOperation;
use LaravelJsonApi\Core\Responses\MetaResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ParseDeleteOperationTest extends TestCase
{
    /**
     * @var Request&MockObject
     */
    private Request&MockObject $request;

    /**
     * @var ParseDeleteOperation
     */
    private ParseDeleteOperation $middleware;

    /**
     * @var DestroyActionInput
     */
    private DestroyActionInput $action;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = new ParseDeleteOperation();

        $this->action = new DestroyActionInput(
            $this->request = $this->createMock(Request::class),
            new ResourceType('tags'),
            new ResourceId('123'),
        );
    }

    /**
     * @return void
     */
    public function test(): void
    {
        $this->request
            ->expects($this->once())
            ->method('json')
            ->with('meta')
            ->willReturn($meta = ['foo' => 'bar']);

        $ref = new Ref(type: $this->action->type(), id: $this->action->id());
        $expected = new MetaResponse($meta);

        $actual = $this->middleware->handle(
            $this->action,
            function (DestroyActionInput $passed) use ($ref, $meta, $expected): MetaResponse {
                $op = $passed->operation();
                $this->assertNotSame($this->action, $passed);
                $this->assertSame($this->action->request(), $passed->request());
                $this->assertSame($this->action->type(), $passed->type());
                $this->assertSame($this->action->id(), $passed->id());
                $this->assertEquals($ref, $op->ref());
                $this->assertSame($meta, $op->meta);
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
    }
}