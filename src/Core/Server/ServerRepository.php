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

namespace LaravelJsonApi\Core\Server;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use InvalidArgumentException;
use LaravelJsonApi\Contracts\Server\Repository as RepositoryContract;
use LaravelJsonApi\Contracts\Server\Server as ServerContract;
use LaravelJsonApi\Core\Support\AppResolver;
use RuntimeException;
use Throwable;

class ServerRepository implements RepositoryContract
{
    /**
     * @var AppResolver
     */
    private AppResolver $app;

    /**
     * @var array
     */
    private array $cache;

    /**
     * ServerRepository constructor.
     *
     * @param AppResolver $app
     */
    public function __construct(AppResolver $app)
    {
        $this->app = $app;
        $this->cache = [];
    }

    /**
     * @inheritDoc
     */
    public function server(string $name): ServerContract
    {
        if (empty($name)) {
            throw new InvalidArgumentException('Expecting a non-empty JSON:API server name.');
        }

        if (isset($this->cache[$name])) {
            return $this->cache[$name];
        }

        $class = $this->config()->get("jsonapi.servers.{$name}");

        if (empty($class) || !class_exists($class)) {
            throw new RuntimeException("Server {$name} does not exist in config or is not a valid class.");
        }

        try {
            $server = new $class($this->app, $name);
        } catch (Throwable $ex) {
            throw new RuntimeException(
                "Unable to construct server {$name} using class {$class}.",
                0,
                $ex
            );
        }

        if ($server instanceof ServerContract) {
            return $this->cache[$name] = $server;
        }

        throw new RuntimeException("Class for server {$name} is not a server instance.");
    }

    /**
     * @return ConfigRepository
     */
    private function config(): ConfigRepository
    {
        return $this->app->instance()->make(ConfigRepository::class);
    }
}
