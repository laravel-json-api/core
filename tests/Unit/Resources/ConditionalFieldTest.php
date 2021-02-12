<?php
/*
 * Copyright 2021 Cloud Creativity Limited
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

use LaravelJsonApi\Core\Resources\ConditionalField;
use PHPUnit\Framework\TestCase;

class ConditionalFieldTest extends TestCase
{

    public function test(): void
    {
        $field = new ConditionalField(true, 'foobar');

        $this->assertFalse($field->skip());
        $this->assertSame('foobar', $field->get());
        $this->assertSame('foobar', $field->jsonSerialize());
    }

    public function testClosure(): void
    {
        $field = new ConditionalField(true, fn() => 'foobar');

        $this->assertSame('foobar', $field->jsonSerialize());
    }

    public function testSkip(): void
    {
        $field = new ConditionalField(false, 'foobar');

        $this->assertTrue($field->skip());

        $this->expectException(\LogicException::class);
        $field->jsonSerialize();
    }
}
