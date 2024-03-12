<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Document\Input\Values;

use LaravelJsonApi\Core\Document\Input\Values\ListOfResourceIdentifiers;
use LaravelJsonApi\Core\Document\Input\Values\ResourceIdentifier;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;
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
