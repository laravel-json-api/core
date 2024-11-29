<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Tests\Integration;

use Illuminate\Container\Container;
use Illuminate\Contracts\Container\Container as ContainerContract;
use Illuminate\Contracts\Translation\Translator;
use LaravelJsonApi\Contracts\Auth\ResourceAuthorizerFactory as ResourceAuthorizerFactoryContract;
use LaravelJsonApi\Contracts\Bus\Commands\Dispatcher as CommandDispatcherContract;
use LaravelJsonApi\Contracts\Bus\Queries\Dispatcher as QueryDispatcherContract;
use LaravelJsonApi\Core\Auth\ResourceAuthorizerFactory;
use LaravelJsonApi\Core\Bus\Commands\Dispatcher as CommandDispatcher;
use LaravelJsonApi\Core\Bus\Queries\Dispatcher as QueryDispatcher;
use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    /**
     * @var Container
     */
    protected Container $container;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->container = new Container();

        /** Laravel */
        $this->container->instance(ContainerContract::class, $this->container);
        $this->container->bind(Translator::class, function () {
            $translator = $this->createMock(Translator::class);
            $translator->method('get')->willReturnCallback(fn (string $value) => $value);
            return $translator;
        });

        /** Laravel JSON:API */
        $this->container->bind(CommandDispatcherContract::class, CommandDispatcher::class);
        $this->container->bind(QueryDispatcherContract::class, QueryDispatcher::class);
        $this->container->bind(ResourceAuthorizerFactoryContract::class, ResourceAuthorizerFactory::class);
    }
}
