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

namespace LaravelJsonApi\Core\Tests\Unit\Resources;

use LaravelJsonApi\Core\Resources\ConditionalField;
use LaravelJsonApi\Core\Resources\ConditionalFields;
use LaravelJsonApi\Core\Resources\ConditionalList;
use PHPUnit\Framework\TestCase;

class ConditionalListTest extends TestCase
{

    public function test(): void
    {
        $values = [
            'foo',
            new ConditionalField(true, 'bar'),
            new ConditionalField(true, fn() => 'baz'),
            new ConditionalField(false, 'boom!'),
            'blah' => 'bazbat',
            new ConditionalFields(true, [
                'a',
                fn() => 'b',
                'blah' => 'c',
            ]),
            new ConditionalFields(false, [
                'd',
                'e',
            ]),
        ];

        $iterator = new ConditionalList($values);

        $this->assertJsonStringEqualsJsonString(json_encode([
            'foo',
            'bar',
            'baz',
            'bazbat',
            'a',
            'b',
            'c',
        ]), json_encode($iterator));
    }

    public function testEmpty(): void
    {
        $iterator = new ConditionalList([]);

        $this->assertSame([], $iterator->jsonSerialize());
    }
}
