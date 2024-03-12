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
