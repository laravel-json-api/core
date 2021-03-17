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
        $schema->method('isSortable')->willReturnCallback(
            fn($value) => \in_array($value, ['title', 'updatedAt', 'createdAt'], true)
        );

        $this->assertTrue(SortField::cast('-updatedAt')->existsOnSchema($schema));
        $this->assertFalse(SortField::cast('id')->existsOnSchema($schema));
    }
}
