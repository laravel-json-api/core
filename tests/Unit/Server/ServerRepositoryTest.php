<?php

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Unit\Server;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Container\Container as IlluminateContainer;
use LaravelJsonApi\Core\Server\ServerRepository;
use LaravelJsonApi\Core\Tests\Unit\Server\Fixture\Server;
use PHPUnit\Framework\TestCase;

final class ServerRepositoryTest extends TestCase
{
    public function testItCreatesAServer(): void
    {
        $serverName = 'v1';
        $serverClass = Server::class;

        $container = $this->createMock(IlluminateContainer::class);
        $config = $this->createMock(ConfigRepository::class);

        $config->expects($this->once())->method('get')->with("jsonapi.servers.{$serverName}")->willReturn($serverClass);

        $expectedServer = new Server($container, $serverName);

        $container->expects($this->once())->method('make')->with(
            $serverClass,
            [
                'container' => $container,
                'name' => $serverName,
            ]
        )->willReturn($expectedServer);

        $serverRepository = new ServerRepository($container, $config);

        $server = $serverRepository->server($serverName);

        $this->assertInstanceOf($serverClass, $server);
        $this->assertSame($expectedServer, $server);
    }
}
