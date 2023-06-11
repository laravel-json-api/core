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

namespace LaravelJsonApi\Core\Tests\Integration;

use Illuminate\Container\Container;
use Illuminate\Contracts\Container\Container as ContainerContract;
use Illuminate\Contracts\Pipeline\Pipeline as PipelineContract;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Pipeline\Pipeline;
use LaravelJsonApi\Contracts\Bus\Commands\Dispatcher as CommandDispatcherContract;
use LaravelJsonApi\Contracts\Bus\Queries\Dispatcher as QueryDispatcherContract;
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
        $this->container->bind(PipelineContract::class, fn() => new Pipeline($this->container));
        $this->container->bind(Translator::class, function () {
            $translator = $this->createMock(Translator::class);
            $translator->method('get')->willReturnCallback(fn (string $value) => $value);
            return $translator;
        });

        /** Laravel JSON:API */
        $this->container->bind(CommandDispatcherContract::class, CommandDispatcher::class);
        $this->container->bind(QueryDispatcherContract::class, QueryDispatcher::class);
    }
}
