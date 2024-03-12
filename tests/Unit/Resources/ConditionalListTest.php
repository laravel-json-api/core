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
