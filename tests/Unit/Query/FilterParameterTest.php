<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Query;

use LaravelJsonApi\Contracts\Schema\Schema;
use LaravelJsonApi\Core\Query\FilterParameter;
use PHPUnit\Framework\TestCase;

class FilterParameterTest extends TestCase
{

    public function test(): void
    {
        $filter = new FilterParameter('foo', 'bar');

        $this->assertSame('foo', $filter->key());
        $this->assertSame('bar', $filter->value());
    }

    public function testExistsOnSchema(): void
    {
        $schema = $this->createMock(Schema::class);
        $schema->method('isFilter')->willReturnCallback(
            fn($value) => \in_array($value, ['foo', 'bar', 'baz'], true)
        );

        $this->assertTrue((new FilterParameter('baz', 'bat'))->existsOnSchema($schema));
        $this->assertFalse((new FilterParameter('foobar', 'bazbat'))->existsOnSchema($schema));
    }
}
