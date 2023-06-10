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

namespace LaravelJsonApi\Core\Tests\Unit\Http\Actions\Store\Middleware;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Document\Input\Parsers\ResourceObjectParser;
use LaravelJsonApi\Core\Document\Input\Values\ResourceObject;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Href;
use LaravelJsonApi\Core\Http\Actions\Store\Middleware\ParseStoreOperation;
use LaravelJsonApi\Core\Http\Actions\Store\StoreActionInput;
use LaravelJsonApi\Core\Responses\DataResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ParseStoreOperationTest extends TestCase
{
    /**
     * @var MockObject&ResourceObjectParser
     */
    private ResourceObjectParser&MockObject $parser;

    /**
     * @var Request&MockObject
     */
    private Request&MockObject $request;

    /**
     * @var ParseStoreOperation
     */
    private ParseStoreOperation $middleware;

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

        $this->middleware = new ParseStoreOperation(
            $this->parser = $this->createMock(ResourceObjectParser::class),
        );

        $this->action = new StoreActionInput(
            $this->request = $this->createMock(Request::class),
            new ResourceType('tags'),
        );
    }

    /**
     * @return void
     */
    public function test(): void
    {
        $data = ['foo' => 'bar'];
        $meta = ['baz' => 'bat'];

        $this->request
            ->expects($this->exactly(2))
            ->method('json')
            ->willReturnCallback(fn (string $key): array => match ($key) {
                'data' => $data,
                'meta' => $meta,
                default => $this->fail('Unexpected json key: ' . $key),
            });

        $this->request
            ->expects($this->once())
            ->method('url')
            ->willReturn($url = '/api/v1/tags');

        $this->parser
            ->expects($this->once())
            ->method('parse')
            ->with($this->identicalTo($data))
            ->willReturn($resource = new ResourceObject(new ResourceType('tags')));

        $expected = new DataResponse(null);

        $actual = $this->middleware->handle(
            $this->action,
            function (StoreActionInput $passed) use ($url, $resource, $meta, $expected): DataResponse {
                $op = $passed->operation();
                $this->assertNotSame($this->action, $passed);
                $this->assertSame($this->action->request(), $passed->request());
                $this->assertSame($this->action->type(), $passed->type());
                $this->assertObjectEquals(new Href($url), $op->href());
                $this->assertSame($resource, $op->data);
                $this->assertSame($meta, $op->meta);
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
    }
}
