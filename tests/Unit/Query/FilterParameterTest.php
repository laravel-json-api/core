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
