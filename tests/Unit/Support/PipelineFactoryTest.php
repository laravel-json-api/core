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
