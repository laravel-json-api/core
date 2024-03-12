<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
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
