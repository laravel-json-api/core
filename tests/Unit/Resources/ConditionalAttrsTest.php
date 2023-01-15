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

namespace LaravelJsonApi\Core\Tests\Unit\Resources;

use LaravelJsonApi\Core\Resources\Concerns\ConditionallyLoadsFields;
use PHPUnit\Framework\TestCase;

class ConditionalAttrsTest extends TestCase
{

    use ConditionallyLoadsFields;

    public function test(): void
    {
        $attrs = $this->mergeWhen(true, $expected = [
            'firstSecret' => 'secret1',
            'secondSecret' => 'secret2',
        ]);

        $this->assertFalse($attrs->skip());

        $this->assertSame($expected, iterator_to_array($attrs));
    }

    public function testClosure(): void
    {
        $expected = [
            'firstSecret' => 'secret1',
            'secondSecret' => 'secret2',
        ];

        $attrs = $this->mergeWhen(true, fn() => $expected);

        $this->assertFalse($attrs->skip());

        $this->assertSame($expected, iterator_to_array($attrs));
    }

    public function testClosureValue(): void
    {
        $expected = [
            'firstSecret' => 'secret1',
            'secondSecret' => 'secret2',
        ];

        $attrs = $this->mergeWhen(true, [
            'firstSecret' => fn() => 'secret1',
            'secondSecret' => 'secret2',
        ]);

        $this->assertFalse($attrs->skip());

        $this->assertSame($expected, iterator_to_array($attrs));
    }

    public function testSkip(): void
    {
        $attrs = $this->mergeWhen(false, []);

        $this->assertTrue($attrs->skip());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Conditional attributes must not be iterated.');

        iterator_to_array($attrs);
    }
}
