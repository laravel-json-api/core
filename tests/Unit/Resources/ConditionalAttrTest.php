<?php
/*
 * Copyright 2020 Cloud Creativity Limited
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

namespace LaravelJsonApi\Core\Tests\Unit\Resources;

use LaravelJsonApi\Core\Resources\Concerns\ConditionallyLoadsAttributes;
use PHPUnit\Framework\TestCase;

class ConditionalAttrTest extends TestCase
{

    use ConditionallyLoadsAttributes;

    public function test(): void
    {
        $attr = $this->when(true, 'foobar');

        $this->assertFalse($attr->skip());
        $this->assertSame('foobar', $attr->jsonSerialize());
    }

    public function testClosure(): void
    {
        $attr = $this->when(true, fn() => 'foobar');

        $this->assertFalse($attr->skip());
        $this->assertSame('foobar', $attr->jsonSerialize());
    }

    public function testSkip(): void
    {
        $attr = $this->when(false, 'foobar');

        $this->assertTrue($attr->skip());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Conditional attribute must not be serialized.');

        $attr->jsonSerialize();
    }
}
