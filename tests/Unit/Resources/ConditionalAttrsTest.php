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
