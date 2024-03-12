<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Support;

use Illuminate\Contracts\Container\Container;
use LaravelJsonApi\Core\Support\PipelineFactory;
use PHPUnit\Framework\TestCase;

class PipelineFactoryTest extends TestCase
{
    /**
     * @return void
     */
    public function test(): void
    {
        $obj1 = new \stdClass();
        $obj2 = new \stdClass();
        $obj3 = new \stdClass();

        $container = $this->createMock(Container::class);
        $container
            ->expects($this->once())
            ->method('make')
            ->with('my-binding')
            ->willReturn(function (object $actual, \Closure $next) use ($obj1, $obj2): object {
                $this->assertSame($obj1, $actual);
                return $next($obj2);
            });

        $factory = new PipelineFactory($container);
        $result = $factory
            ->pipe($obj1)
            ->through(['my-binding'])
            ->then(function (object $actual) use ($obj2, $obj3): object {
                $this->assertSame($actual, $obj2);
                return $obj3;
            });

        $this->assertSame($result, $obj3);
    }
}
