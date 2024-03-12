<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Support;

use LaravelJsonApi\Contracts\Support\Result as ResultContract;
use LaravelJsonApi\Core\Document\Error;
use LaravelJsonApi\Core\Document\ErrorList;
use LaravelJsonApi\Core\Support\Result;
use PHPUnit\Framework\TestCase;

class ResultTest extends TestCase
{
    /**
     * @return void
     */
    public function testItIsSuccessful(): void
    {
        $result = Result::ok();

        $this->assertInstanceOf(ResultContract::class, $result);
        $this->assertTrue($result->didSucceed());
        $this->assertFalse($result->didFail());
        $this->assertEmpty($result->errors());
    }

    /**
     * @return void
     */
    public function testItFailed(): void
    {
        $result = Result::failed();

        $this->assertFalse($result->didSucceed());
        $this->assertTrue($result->didFail());
        $this->assertEmpty($result->errors());
    }

    /**
     * @return void
     */
    public function testItFailedWithErrors(): void
    {
        $result = Result::failed($errors = new ErrorList());

        $this->assertFalse($result->didSucceed());
        $this->assertTrue($result->didFail());
        $this->assertSame($errors, $result->errors());
    }

    /**
     * @return void
     */
    public function testItFailedWithError(): void
    {
        $result = Result::failed($error = new Error());

        $this->assertFalse($result->didSucceed());
        $this->assertTrue($result->didFail());
        $this->assertSame([$error], $result->errors()->all());
    }
}
