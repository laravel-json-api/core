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
use LaravelJsonApi\Core\Query\Input\QueryMany;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\TestCase;

class QueryManyTest extends TestCase
{
    /**
     * @return void
     */
    public function test(): void
    {
        $query = new QueryMany(
            $type = new ResourceType('posts'),
            $params = ['foo' => 'bar'],
        );

        $this->assertSame(QueryCodeEnum::Many, $query->code);
        $this->assertSame($type, $query->type);
        $this->assertSame($params, $query->parameters);
        $this->assertTrue($query->isMany());
        $this->assertFalse($query->isOne());
        $this->assertFalse($query->isRelated());
        $this->assertFalse($query->isRelationship());
        $this->assertFalse($query->isRelatedOrRelationship());
        $this->assertNull($query->getFieldName());
    }
}
