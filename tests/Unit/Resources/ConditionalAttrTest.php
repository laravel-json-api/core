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

class ConditionalAttrTest extends TestCase
{

    use ConditionallyLoadsFields;

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
