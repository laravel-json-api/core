<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Server;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use LaravelJsonApi\Core\Server\ServerRepository;
use LaravelJsonApi\Core\Support\AppResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ServerWithAttributesTest extends TestCase
{
    /**
     * @var MockObject&Application
     */
    private Application&MockObject $app;

    /**
     * @var MockObject&ConfigRepository
     */
    private ConfigRepository&MockObject $config;

    /**
     * @var AppResolver
     */
    private AppResolver $resolver;

    /**
     * @var ServerRepository
     */
    private ServerRepository $repository;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->app = $this->createMock(Application::class);
        $this->config = $this->createMock(ConfigRepository::class);
        $this->resolver = new AppResolver(fn() => $this->app);
        $this->repository = new ServerRepository($this->resolver);
    }

    /**
     * @return void
     */
    public function test(): void
    {
        $expected = $this->willMakeServer($name = 'v1');
        $actual = $this->repository->server($name);

        $this->assertInstanceOf(TestServerWithServeSchemaAttribute::class, $actual);
        $this->assertEquals($expected, $actual);
        $this->assertTrue($actual->schemas()->existsForModel(TestSchema::$model));
    }


    /**
     * @param string $name
     * @param string $class
     * @return TestServer
     */
    private function willMakeServer(string $name, string $class = TestServerWithServeSchemaAttribute::class): TestServerWithServeSchemaAttribute
    {
        $this->app
            ->method('make')
            ->with(ConfigRepository::class)
            ->willReturn($this->config);

        $this->config
            ->expects($this->once())
            ->method('get')
            ->with("jsonapi.servers.{$name}")
            ->willReturn($class);

        return new TestServerWithServeSchemaAttribute($this->resolver, $name);
    }
}
