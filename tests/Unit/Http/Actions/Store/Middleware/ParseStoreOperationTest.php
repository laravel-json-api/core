<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Http\Actions\Store\Middleware;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Document\Input\Parsers\ResourceObjectParser;
use LaravelJsonApi\Core\Document\Input\Values\ResourceObject;
use LaravelJsonApi\Core\Http\Actions\Store\Middleware\ParseStoreOperation;
use LaravelJsonApi\Core\Http\Actions\Store\StoreActionInput;
use LaravelJsonApi\Core\Responses\DataResponse;
use LaravelJsonApi\Core\Values\ResourceType;
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

        $this->parser
            ->expects($this->once())
            ->method('parse')
            ->with($this->identicalTo($data))
            ->willReturn($resource = new ResourceObject(new ResourceType('tags')));

        $expected = new DataResponse(null);

        $actual = $this->middleware->handle(
            $this->action,
            function (StoreActionInput $passed) use ($resource, $meta, $expected): DataResponse {
                $op = $passed->operation();
                $this->assertNotSame($this->action, $passed);
                $this->assertSame($this->action->request(), $passed->request());
                $this->assertSame($this->action->type(), $passed->type());
                $this->assertNull($op->target);
                $this->assertSame($resource, $op->data);
                $this->assertSame($meta, $op->meta);
                return $expected;
            },
        );

        $this->assertSame($expected, $actual);
    }
}
