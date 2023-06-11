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
