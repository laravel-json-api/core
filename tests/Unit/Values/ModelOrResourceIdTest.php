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

namespace LaravelJsonApi\Core\Tests\Unit\Values;

use LaravelJsonApi\Core\Values\ModelOrResourceId;
use LaravelJsonApi\Core\Values\ResourceId;
use PHPUnit\Framework\TestCase;

class ModelOrResourceIdTest extends TestCase
{
    /**
     * @return void
     */
    public function testItIsResourceId(): void
    {
        $modelOrResourceId = new ModelOrResourceId($id = new ResourceId('123'));

        $this->assertSame($id, $modelOrResourceId->id());
        $this->assertNull($modelOrResourceId->model());

        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage('Expecting a model to be set.');
        $modelOrResourceId->modelOrFail();
    }

    /**
     * @return void
     */
    public function testItIsStringId(): void
    {
        $modelOrResourceId = new ModelOrResourceId('999');

        $this->assertObjectEquals(new ResourceId('999'), $modelOrResourceId->id());
        $this->assertNull($modelOrResourceId->model());

        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage('Expecting a model to be set.');
        $modelOrResourceId->modelOrFail();
    }

    /**
     * @return void
     */
    public function testItIsModel(): void
    {
        $modelOrResourceId = new ModelOrResourceId($model = new \stdClass());

        $this->assertNull($modelOrResourceId->id());
        $this->assertSame($model, $modelOrResourceId->model());
        $this->assertSame($model, $modelOrResourceId->modelOrFail());
    }
}
