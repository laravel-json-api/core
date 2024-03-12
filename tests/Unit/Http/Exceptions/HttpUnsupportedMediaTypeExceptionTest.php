<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
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
