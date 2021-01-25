<?php
/**
 * Copyright 2020 Cloud Creativity Limited
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

namespace LaravelJsonApi\Core\Server;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Container\Container as IlluminateContainer;
use InvalidArgumentException;
use LaravelJsonApi\Contracts\Server\Repository as RepositoryContract;
use LaravelJsonApi\Contracts\Server\Server as ServerContract;
use RuntimeException;
use Throwable;

class ServerRepository implements RepositoryContract
{

    /**
     * @var IlluminateContainer
     */
    private IlluminateContainer $container;

    /**
     * @var ConfigRepository
     */
    private ConfigRepository $config;

    /**
     * ServerRepository constructor.
     *
     * @param IlluminateContainer $container
     * @param ConfigRepository $config
     */
    public function __construct(IlluminateContainer $container, ConfigRepository $config)
    {
        $this->container = $container;
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public function server(string $name): ServerContract
    {
        if (empty($name)) {
            throw new InvalidArgumentException('Expecting a non-empty JSON API server name.');
        }

        $class = $this->config->get("jsonapi.servers.{$name}");

        if (empty($class) || !class_exists($class)) {
            throw new RuntimeException("Server {$name} does not exist in config or is not a valid class.");
        }

        try {
            $server = new $class($this->container, $name);
        } catch (Throwable $ex) {
            throw new RuntimeException(
                "Unable to construct server {$name} using class {$class}.",
                0,
                $ex
            );
        }

        if ($server instanceof ServerContract) {
            return $server;
        }

        throw new RuntimeException("Class for server {$name} is not a server instance.");
    }
}
