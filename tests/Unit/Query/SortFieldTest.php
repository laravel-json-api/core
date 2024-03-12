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
use LaravelJsonApi\Core\Query\SortField;
use PHPUnit\Framework\TestCase;

class SortFieldTest extends TestCase
{

    public function testAscending(): void
    {
        $field = SortField::cast('title');

        $this->assertSame('title', (string) $field);
        $this->assertSame('title', $field->name());
        $this->assertSame('asc', $field->getDirection());
        $this->assertTrue($field->isAscending());
        $this->assertFalse($field->isDescending());
        $this->assertEquals($field, SortField::ascending('title'));
    }

    public function testDescending(): void
    {
        $field = SortField::cast('-title');

        $this->assertSame('-title', (string) $field);
        $this->assertSame('title', $field->name());
        $this->assertSame('desc', $field->getDirection());
        $this->assertFalse($field->isAscending());
        $this->assertTrue($field->isDescending());
        $this->assertEquals($field, SortField::descending('title'));
    }

    public function testExistsOnSchema(): void
    {
        $schema = $this->createMock(Schema::class);
        $schema->method('isSortField')->willReturnCallback(
            fn($value) => \in_array($value, ['title', 'updatedAt', 'createdAt'], true)
        );

        $this->assertTrue(SortField::cast('-updatedAt')->existsOnSchema($schema));
        $this->assertFalse(SortField::cast('id')->existsOnSchema($schema));
    }
}
