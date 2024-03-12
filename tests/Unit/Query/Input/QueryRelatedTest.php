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
use LaravelJsonApi\Core\Query\Input\QueryRelated;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\TestCase;

class QueryRelatedTest extends TestCase
{
    /**
     * @return void
     */
    public function test(): void
    {
        $query = new QueryRelated(
            $type = new ResourceType('posts'),
            $id = new ResourceId('999'),
            $fieldName = 'comments',
            $params = ['foo' => 'bar'],
        );

        $this->assertSame(QueryCodeEnum::Related, $query->code);
        $this->assertSame($type, $query->type);
        $this->assertSame($id, $query->id);
        $this->assertSame($fieldName, $query->fieldName);
        $this->assertSame($params, $query->parameters);
        $this->assertFalse($query->isMany());
        $this->assertFalse($query->isOne());
        $this->assertTrue($query->isRelated());
        $this->assertFalse($query->isRelationship());
        $this->assertTrue($query->isRelatedOrRelationship());
        $this->assertSame($fieldName, $query->getFieldName());
    }
}
