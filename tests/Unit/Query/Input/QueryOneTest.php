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

namespace LaravelJsonApi\Core\Tests\Unit\Query\Input;

use LaravelJsonApi\Core\Query\Input\QueryCodeEnum;
use LaravelJsonApi\Core\Query\Input\QueryOne;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\TestCase;

class QueryOneTest extends TestCase
{
    /**
     * @return void
     */
    public function test(): void
    {
        $query = new QueryOne(
            $type = new ResourceType('posts'),
            $id = new ResourceId('123'),
            $params = ['foo' => 'bar'],
        );

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
