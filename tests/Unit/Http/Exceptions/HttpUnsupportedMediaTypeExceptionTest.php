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

namespace LaravelJsonApi\Core\Tests\Unit\Http\Exceptions;

use LaravelJsonApi\Core\Http\Exceptions\HttpUnsupportedMediaTypeException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class HttpUnsupportedMediaTypeExceptionTest extends TestCase
{
    /**
     * @return void
     */
    public function test(): void
    {
        $ex = new HttpUnsupportedMediaTypeException();

        $this->assertInstanceOf(HttpExceptionInterface::class, $ex);
        $this->assertEmpty($ex->getMessage());
        $this->assertSame(415, $ex->getStatusCode());
        $this->assertEmpty($ex->getHeaders());
        $this->assertNull($ex->getPrevious());
        $this->assertSame(0, $ex->getCode());
    }

    /**
     * @return void
     */
    public function testWithOptionalParameters(): void
    {
        $ex = new HttpUnsupportedMediaTypeException(
            $msg = 'Unsupported!',
            $previous = new \LogicException(),
            $headers = ['X-Foo' => 'Bar'],
            $code = 99,
        );

        $this->assertSame($msg, $ex->getMessage());
        $this->assertSame(415, $ex->getStatusCode());
        $this->assertSame($headers, $ex->getHeaders());
        $this->assertSame($previous, $ex->getPrevious());
        $this->assertSame($code, $ex->getCode());
    }
}
