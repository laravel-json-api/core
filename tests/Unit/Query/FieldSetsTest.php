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

use LaravelJsonApi\Core\Query\FieldSet;
use LaravelJsonApi\Core\Query\FieldSets;
use PHPUnit\Framework\TestCase;

class FieldSetsTest extends TestCase
{

    public function testCastWithArray(): FieldSets
    {
        $fields = FieldSets::fromArray($values = [
            'posts' => 'slug,synopsis,title',
            'users' => 'firstName,lastName',
        ]);

        $this->assertSame(['slug', 'synopsis', 'title'], $fields->get('posts')->fields());
        $this->assertSame(['firstName', 'lastName'], $fields->get('users')->fields());
        $this->assertNull($fields->get('comments'));

        $this->assertEquals($values, $fields->toArray());
        $this->assertEquals($fields, FieldSets::nullable($values));
        $this->assertCount(2, $fields);

        return $fields;
    }

    public function testCastNull(): FieldSets
    {
        $fields = FieldSets::cast(null);

        $this->assertInstanceOf(FieldSets::class, $fields);
        $this->assertSame([], $fields->toArray());
        $this->assertNull(FieldSets::nullable(null));

        return $fields;
    }

    public function testCastEmptyString(): void
    {
        $fields = FieldSets::cast([
            'posts' => '',
        ]);

        $this->assertEquals(new FieldSet('posts', []), $fields->get('posts'));
    }

    /**
     * @param FieldSets $fields
     * @depends testCastWithArray
     */
    public function testNotEmpty(FieldSets $fields): void
    {
        $this->assertTrue($fields->isNotEmpty());
        $this->assertFalse($fields->isEmpty());
    }

    /**
     * @param FieldSets $fields
     * @depends testCastNull
     */
    public function testEmpty(FieldSets $fields): void
    {
        $this->assertTrue($fields->isEmpty());
        $this->assertFalse($fields->isNotEmpty());
    }

    /**
     * @param FieldSets $fields
     * @depends testCastWithArray
     */
    public function testIterator(FieldSets $fields): void
    {
        $values = [];

        foreach ($fields as $resourceType => $fieldSet) {
            $values[$resourceType] = [];

            foreach ($fieldSet as $field) {
                $values[$resourceType][] = $field;
            }
        }

        $this->assertSame([
            'posts' => ['slug', 'synopsis', 'title'],
            'users' => ['firstName', 'lastName'],
        ], $values);
    }

    /**
     * @param FieldSets $fields
     * @depends testCastWithArray
     */
    public function testAllAndCollect(FieldSets $fields): void
    {
        $expected = [
            'posts' => new FieldSet('posts', ['slug', 'synopsis', 'title']),
            'users' => new FieldSet('users', ['firstName', 'lastName']),
        ];

        $this->assertEquals($expected, $fields->all());
        $this->assertEquals(collect($expected), $fields->collect());
    }

    /**
     * @param FieldSets $fields
     * @depends testCastWithArray
     */
    public function testFields(FieldSets $fields): void
    {
        $expected = [
            'posts' => ['slug', 'synopsis', 'title'],
            'users' => ['firstName', 'lastName'],
        ];

        $this->assertEquals($expected, $fields->fields());
    }

    public function testForget(): void
    {
        $fields = FieldSets::fromArray([
            'posts' => 'slug,synopsis,title',
            'users' => 'firstName,lastName',
        ]);

        $this->assertSame($fields, $fields->forget('foo', 'users', 'bar'));

        $this->assertSame([
            'posts' => 'slug,synopsis,title',
        ], $fields->toArray());
    }
}
