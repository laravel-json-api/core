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

namespace LaravelJsonApi\Core\Tests\Unit\Document\Input\Values;

use LaravelJsonApi\Core\Document\Input\Values\ListOfResourceIdentifiers;
use LaravelJsonApi\Core\Document\Input\Values\ResourceId;
use LaravelJsonApi\Core\Document\Input\Values\ResourceIdentifier;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;
use PHPUnit\Framework\TestCase;

class ListOfResourceIdentifiersTest extends TestCase
{
    /**
     * @return void
     */
    public function testItIsNotEmpty(): void
    {
        $identifiers = new ListOfResourceIdentifiers(
            $a = new ResourceIdentifier(
                type: new ResourceType('posts'),
                id: new ResourceId('123'),
            ),
            $b = new ResourceIdentifier(
                type: new ResourceType('comments'),
                lid: new ResourceId('456'),
            ),
        );

        $this->assertSame([$a, $b], iterator_to_array($identifiers));
        $this->assertSame([$a, $b], $identifiers->all());
        $this->assertCount(2, $identifiers);
        $this->assertTrue($identifiers->isNotEmpty());
        $this->assertFalse($identifiers->isEmpty());
        $this->assertSame([$a->toArray(), $b->toArray()], $identifiers->toArray());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['data' => [$a, $b]]),
            json_encode(['data' => $identifiers]),
        );
    }

    /**
     * @return void
     */
    public function testItIsEmpty(): void
    {
        $identifiers = new ListOfResourceIdentifiers();

        $this->assertEmpty(iterator_to_array($identifiers));
        $this->assertEmpty($identifiers->all());
        $this->assertCount(0, $identifiers);
        $this->assertFalse($identifiers->isNotEmpty());
        $this->assertTrue($identifiers->isEmpty());
        $this->assertSame([], $identifiers->toArray());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['data' => []]),
            json_encode(['data' => $identifiers]),
        );
    }
}
