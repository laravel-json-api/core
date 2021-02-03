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

namespace LaravelJsonApi\Core\Tests\Unit\Resources;

use Illuminate\Support\Collection;
use LaravelJsonApi\Contracts\Resources\Container;
use LaravelJsonApi\Core\Resources\ResourceIterator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ResourceIteratorTest extends TestCase
{

    /**
     * @var Container|MockObject
     */
    private Container $container;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->container = $this->createMock(Container::class);
    }

    public function testIterable(): void
    {
        $this->container->expects($this->once())->method('cursor')->willReturnCallback(function ($values) {
            foreach ($values as $value) {
                yield strtoupper($value);
            }
        });

        $models = new Collection(['a', 'b', 'c']);

        $iterator = new ResourceIterator($this->container, $models);

        $this->assertSame(['A', 'B', 'C'], iterator_to_array($iterator));
        $this->assertSame(['A', 'B', 'C'], iterator_to_array($iterator));
    }

    public function testArray(): void
    {
        $this->container->expects($this->once())->method('cursor')->willReturnCallback(function ($values) {
            foreach ($values as $value) {
                yield strtoupper($value);
            }
        });

        $iterator = new ResourceIterator($this->container, ['a', 'b', 'c']);

        $this->assertSame(['A', 'B', 'C'], iterator_to_array($iterator));
        $this->assertSame(['A', 'B', 'C'], iterator_to_array($iterator));
    }

    public function testGenerator(): void
    {
        $this->container->expects($this->once())->method('cursor')->willReturnCallback(function ($values) {
            foreach ($values as $value) {
                yield strtoupper($value);
            }
        });

        $func = function () {
            yield from ['a', 'b', 'c'];
        };

        $iterator = new ResourceIterator($this->container, $func());

        $this->assertSame(['A', 'B', 'C'], iterator_to_array($iterator));
        $this->assertSame(['A', 'B', 'C'], iterator_to_array($iterator));
    }


}
