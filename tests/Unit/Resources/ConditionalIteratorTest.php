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

use LaravelJsonApi\Core\Resources\ConditionalAttr;
use LaravelJsonApi\Core\Resources\ConditionalAttrs;
use LaravelJsonApi\Core\Resources\ConditionalIterator;
use PHPUnit\Framework\TestCase;

class ConditionalIteratorTest extends TestCase
{

    public function test(): void
    {
        $attrs = [
            'foo' => 'bar',
            'baz' => new ConditionalAttr(true, 'bat'),
            'foobar' => new ConditionalAttr(false, 'bazbat'),
            new ConditionalAttrs(true, [
                'a' => 'b',
                'c' => fn() => 'd',
            ]),
            new ConditionalAttrs(false, [
                'e' => 'f',
            ]),
        ];

        $iterator = new ConditionalIterator($attrs);

        $this->assertJsonStringEqualsJsonString(json_encode([
            'foo' => 'bar',
            'baz' => 'bat',
            'a' => 'b',
            'c' => 'd',
        ]), json_encode($iterator));
    }

    public function testEmpty(): void
    {
        $iterator = new ConditionalIterator([]);

        $this->assertNull($iterator->jsonSerialize());
    }
}
