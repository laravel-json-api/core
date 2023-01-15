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

namespace LaravelJsonApi\Core\Tests\Unit\Query;

use LaravelJsonApi\Core\Query\SortField;
use LaravelJsonApi\Core\Query\SortFields;
use PHPUnit\Framework\TestCase;

class SortFieldsTest extends TestCase
{

    public function testCastWithString(): SortFields
    {
        $fields = SortFields::cast($value = 'title,-id');

        $this->assertEquals($expected = [
            SortField::ascending('title'),
            SortField::descending('id'),
        ], $fields->all());

        $this->assertEquals(collect($expected), $fields->collect());
        $this->assertEquals($expected, iterator_to_array($fields));
        $this->assertEquals(['title', '-id'], $fields->toArray());
        $this->assertSame($value, (string) $fields);

        return $fields;
    }

    public function testCastWithArray(): void
    {
        $fields = SortFields::cast(['title', '-id']);

        $this->assertEquals([
            SortField::ascending('title'),
            SortField::descending('id'),
        ], $fields->all());
    }

    public function testCastWithEnumerable(): void
    {
        $fields = SortFields::cast(collect(['title', '-id']));

        $this->assertEquals([
            SortField::ascending('title'),
            SortField::descending('id'),
        ], $fields->all());
    }

    public function testCastWithField(): void
    {
        $field = SortField::descending('id');
        $fields = SortFields::cast($field);

        $this->assertEquals([$field], $fields->all());
    }

    public function testCastWithNull(): void
    {
        $fields = SortFields::cast(null);

        $this->assertCount(0, $fields);
        $this->assertTrue($fields->isEmpty());
        $this->assertFalse($fields->isNotEmpty());
    }

    /**
     * @param SortFields $fields
     * @depends testCastWithString
     */
    public function testCountAndNotEmpty(SortFields $fields): void
    {
        $this->assertCount(2, $fields);
        $this->assertTrue($fields->isNotEmpty());
        $this->assertFalse($fields->isEmpty());
    }

    /**
     * @param SortFields $expected
     * @depends testCastWithString
     */
    public function testNullable(SortFields $expected): void
    {
        $this->assertNull(SortFields::nullable(null));
        $this->assertEquals(new SortFields(), SortFields::nullable([]));
        $this->assertEquals($expected, SortFields::nullable($expected->toArray()));
    }

    public function testFilter(): void
    {
        $fields = SortFields::fromString('-updatedAt,-createdAt,id');

        $actual = $fields->filter(fn(SortField $field) => $field->isDescending());

        $this->assertNotSame($fields, $actual);
        $this->assertSame('-updatedAt,-createdAt,id', $fields->toString());
        $this->assertSame('-updatedAt,-createdAt', $actual->toString());
    }

    public function testReject(): void
    {
        $fields = SortFields::fromString('-updatedAt,-createdAt,id');

        $actual = $fields->reject(fn(SortField $field) => $field->isAscending());

        $this->assertNotSame($fields, $actual);
        $this->assertSame('-updatedAt,-createdAt,id', $fields->toString());
        $this->assertSame('-updatedAt,-createdAt', $actual->toString());
    }
}
