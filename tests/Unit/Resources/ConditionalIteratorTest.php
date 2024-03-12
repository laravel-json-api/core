<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Resources;

use LaravelJsonApi\Core\Resources\ConditionalField;
use LaravelJsonApi\Core\Resources\ConditionalFields;
use LaravelJsonApi\Core\Resources\ConditionalIterator;
use PHPUnit\Framework\TestCase;

class ConditionalIteratorTest extends TestCase
{

    public function test(): void
    {
        $attrs = [
            'foo' => 'bar',
            'baz' => new ConditionalField(true, 'bat'),
            'foobar' => new ConditionalField(false, 'bazbat'),
            new ConditionalFields(true, [
                'a' => 'b',
                'c' => fn() => 'd',
            ]),
            new ConditionalFields(false, [
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
