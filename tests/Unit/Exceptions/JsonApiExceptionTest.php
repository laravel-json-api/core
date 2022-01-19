<?php
/*
 * Copyright 2022 Cloud Creativity Limited
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

namespace LaravelJsonApi\Core\Tests\Unit\Exceptions;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use LaravelJsonApi\Contracts\ErrorProvider;
use LaravelJsonApi\Core\Document\Error;
use LaravelJsonApi\Core\Document\ErrorList;
use LaravelJsonApi\Core\Document\JsonApi;
use LaravelJsonApi\Core\Document\Link;
use LaravelJsonApi\Core\Document\Links;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Core\Json\Hash;
use LaravelJsonApi\Core\Responses\ErrorResponse;
use PHPUnit\Framework\TestCase;

class JsonApiExceptionTest extends TestCase
{

    public function testErrorList(): void
    {
        $mock = $this->createMock(ErrorList::class);
        $mock->method('status')->willReturn(422);

        $headers = ['X-Foo' => 'Bar'];

        $exception = new JsonApiException($mock, $previous = new \Exception(), $headers);

        $this->assertSame('JSON:API error', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
        $this->assertSame(422, $exception->getStatusCode());
        $this->assertSame($headers, $exception->getHeaders());
        $this->assertSame($mock, $exception->getErrors());
        $this->assertSame($mock, $exception->toErrors());
    }

    public function testErrorProvider(): void
    {
        $list = $this->createMock(ErrorList::class);
        $provider = $this->createMock(ErrorProvider::class);
        $provider->method('toErrors')->willReturn($list);

        $exception = new JsonApiException($provider);

        $this->assertSame($list, $exception->toErrors());
        $this->assertSame($list, $exception->getErrors());
    }

    public function testError(): void
    {
        $mock = $this->createMock(Error::class);
        $expected = new ErrorList($mock);

        $exception = new JsonApiException($mock);

        $this->assertEquals($expected, $exception->toErrors());
        $this->assertEquals($expected, $exception->getErrors());
    }

    public function testErrors(): void
    {
        $mock1 = $this->createMock(Error::class);
        $mock2 = $this->createMock(Error::class);
        $expected = new ErrorList($mock1, $mock2);

        $exception = new JsonApiException([$mock1, $mock2]);

        $this->assertEquals($expected, $exception->toErrors());
        $this->assertEquals($expected, $exception->getErrors());
    }

    /**
     * @return array[]
     */
    public function is4xxProvider(): array
    {
        return [
            [100, false],
            [200, false],
            [300, false],
            [399, false],
            [400, true],
            [499, true],
            [500, false],
        ];
    }

    /**
     * @param int $value
     * @param bool $expected
     * @dataProvider is4xxProvider
     */
    public function testIs4xx(int $value, bool $expected): void
    {
        $mock = $this->createMock(ErrorList::class);
        $mock->method('status')->willReturn($value);

        $exception = new JsonApiException($mock);

        $this->assertSame($expected, $exception->is4xx());
    }

    /**
     * @return array[]
     */
    public function is5xxProvider(): array
    {
        return [
            [100, false],
            [200, false],
            [300, false],
            [400, false],
            [499, false],
            [500, true],
            [599, true],
            [600, false],
        ];
    }

    /**
     * @param int $value
     * @param bool $expected
     * @dataProvider is5xxProvider
     */
    public function testIs5xx(int $value, bool $expected): void
    {
        $mock = $this->createMock(ErrorList::class);
        $mock->method('status')->willReturn($value);

        $exception = new JsonApiException($mock);

        $this->assertSame($expected, $exception->is5xx());
    }

    public function testToResponse(): void
    {
        $request = $this->createMock(Request::class);

        $jsonApi = new JsonApi('1.0');
        $meta = new Hash(['foo' => 'bar']);
        $links = new Links(new Link('test', '/api/test'));
        $headers = ['X-Foo' => 'Bar'];

        $response = $this->createMock(ErrorResponse::class);
        $response
            ->expects($this->once())
            ->method('withJsonApi')
            ->with($this->identicalTo($jsonApi))
            ->willReturnSelf();
        $response
            ->expects($this->once())
            ->method('withMeta')
            ->with($meta)
            ->willReturnSelf();
        $response
            ->expects($this->once())
            ->method('withLinks')
            ->with($this->identicalTo($links))
            ->willReturnSelf();
        $response
            ->expects($this->once())
            ->method('withHeaders')
            ->with($headers)
            ->willReturnSelf();
        $response
            ->expects($this->once())
            ->method('toResponse')
            ->with($this->identicalTo($request))
            ->willReturn($expected = $this->createMock(Response::class));

        $list = $this->createMock(ErrorList::class);
        $list->method('prepareResponse')->willReturn($response);

        $actual = JsonApiException::make($list)
            ->withJsonApi($jsonApi)
            ->withMeta($meta)
            ->withLinks($links)
            ->withHeaders($headers)
            ->toResponse($request);

        $this->assertSame($expected, $actual);
    }
}
