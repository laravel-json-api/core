<?php
/*
 * Copyright 2021 Cloud Creativity Limited
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

namespace LaravelJsonApi\Core\Tests\Unit\Server;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Container\Container as IlluminateContainer;
use LaravelJsonApi\Core\Server\ServerRepository;
use PHPUnit\Framework\TestCase;

class ServerRepositoryTest extends TestCase
{

    public function test(): void
    {
        $name = 'v1';
        $klass = TestServer::class;

        $container = $this->createMock(IlluminateContainer::class);
        $config = $this->createMock(ConfigRepository::class);

        $config
            ->expects($this->once())
            ->method('get')
            ->with("jsonapi.servers.{$name}")
            ->willReturn($klass);

        $expected = new TestServer($container, $name);

        $repository = new ServerRepository($container, $config);

        $actual = $repository->server($name);

        $this->assertInstanceOf($klass, $actual);
        $this->assertEquals($expected, $actual);

        /** We expect the server to only be constructed once. */
        $this->assertSame($actual, $repository->server($name));
    }
}
