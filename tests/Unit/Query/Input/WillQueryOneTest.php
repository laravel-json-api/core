<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Query\Input;

use LaravelJsonApi\Core\Query\Input\QueryCodeEnum;
use LaravelJsonApi\Core\Query\Input\QueryOne;
use LaravelJsonApi\Core\Query\Input\WillQueryOne;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\TestCase;

class WillQueryOneTest extends TestCase
{
    /**
     * @return void
     */
    public function test(): void
    {
        $query = new WillQueryOne(
            $type = new ResourceType('posts'),
            $params = ['foo' => 'bar'],
        );

        $this->assertSame(QueryCodeEnum::One, $query->code);
        $this->assertSame($type, $query->type);
        $this->assertSame($params, $query->parameters);
        $this->assertFalse($query->isMany());
        $this->assertTrue($query->isOne());
        $this->assertFalse($query->isRelated());
        $this->assertFalse($query->isRelationship());
        $this->assertFalse($query->isRelatedOrRelationship());
        $this->assertNull($query->getFieldName());
    }

    /**
     * @return void
     */
    public function testItCanSetId(): void
    {
        $query = new WillQueryOne(
            $type = new ResourceType('posts'),
            $params = ['foo' => 'bar'],
        );

        $query = $query->withId($id = new ResourceId('123'));

        $this->assertInstanceOf(QueryOne::class, $query);
        $this->assertSame(QueryCodeEnum::One, $query->code);
        $this->assertSame($type, $query->type);
        $this->assertSame($id, $query->id);
        $this->assertSame($params, $query->parameters);
        $this->assertFalse($query->isMany());
        $this->assertTrue($query->isOne());
        $this->assertFalse($query->isRelated());
        $this->assertFalse($query->isRelationship());
        $this->assertFalse($query->isRelatedOrRelationship());
        $this->assertNull($query->getFieldName());
    }
}
