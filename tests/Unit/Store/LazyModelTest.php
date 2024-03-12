<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Store;

use LaravelJsonApi\Contracts\Store\Store;
use LaravelJsonApi\Core\Store\LazyModel;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LazyModelTest extends TestCase
{
    /**
     * @var MockObject&Store
     */
    private Store&MockObject $store;

    /**
     * @var ResourceType
     */
    private ResourceType $type;

    /**
     * @var ResourceId
     */
    private ResourceId $id;

    /**
     * @var LazyModel
     */
    private LazyModel $lazy;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->store = $this->createMock(Store::class);
        $this->type = new ResourceType('tags');
        $this->id = new ResourceId('8e759a8e-8bd1-4e38-ad65-c72ba32f3a75');
        $this->lazy = new LazyModel($this->store, $this->type, $this->id);
    }

    /**
     * @return void
     */
    public function testItGetsModelOnce(): void
    {
        $this->store
            ->expects($this->once())
            ->method('find')
            ->with($this->identicalTo($this->type), $this->identicalTo($this->id))
            ->willReturn($model = new \stdClass());

        $this->assertSame($model, $this->lazy->get());
        $this->assertSame($model, $this->lazy->get());
        $this->assertSame($model, $this->lazy->getOrFail());
        $this->assertSame($model, $this->lazy->getOrFail());
    }

    /**
     * @return void
     */
    public function testItDoesNotGetModelOnce(): void
    {
        $this->store
            ->expects($this->once())
            ->method('find')
            ->with($this->identicalTo($this->type), $this->identicalTo($this->id))
            ->willReturn(null);

        $this->assertNull($this->lazy->get());
        $this->assertNull($this->lazy->get());

        $this->expectException(\AssertionError::class);
        $this->expectExceptionMessage(
            'Resource of type tags and id 8e759a8e-8bd1-4e38-ad65-c72ba32f3a75 does not exist.',
        );

        $this->lazy->getOrFail();
    }

    /**
     * @return void
     */
    public function testItIsEqual(): void
    {
        $this->assertObjectEquals($this->lazy, clone $this->lazy);
    }

    /**
     * @return void
     */
    public function testItIsNotEqual(): void
    {
        $a = new LazyModel($this->store, new ResourceType('posts'), clone $this->id);
        $b = new LazyModel($this->store, clone $this->type, new ResourceId('0fc2582f-7f88-4c40-9e18-042f2856f206'));
        $c = new LazyModel($this->createMock(Store::class), $this->type, $this->id);

        $this->assertFalse($this->lazy->equals($a));
        $this->assertFalse($this->lazy->equals($b));
        $this->assertFalse($this->lazy->equals($c));
    }
}
