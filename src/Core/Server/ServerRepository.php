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
     * @var array<string, ServerContract>
     */
    private array $cache = [];

    /**
     * @var array<string, class-string<ServerContract>>
     */
    private array $classes = [];

    /**
     * ServerRepository constructor.
     *
     * @param AppResolver $app
     */
    public function __construct(private readonly AppResolver $app)
    {
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

        return $this->cache[$name] =  $this->cache[$name] ?? $this->make($name);
    }

    /**
     * Use a server once, without thread-caching it.
     *
     * @param string $name
     * @return ServerContract
     * TODO add to interface
     */
    public function once(string $name): ServerContract
    {
        return $this->make($name);
    }

    /**
     * @param string $name
     * @return ServerContract
     */
    private function make(string $name): ServerContract
    {
        $class = $this->classes[$name] ?? $this->config()->get("jsonapi.servers.{$name}");

        assert(
            !empty($class) && class_exists($class) && is_a($class, ServerContract::class, true),
            "JSON:API server '{$name}' does not exist in config or is not a valid class.",
        );

        $this->classes[$name] = $class;

        try {
            $server = new $class($this->app, $name);
        } catch (Throwable $ex) {
            throw new RuntimeException(
                "Unable to construct JSON:API server {$name} using class {$class}.",
                0,
                $ex,
            );
        }

        assert($server instanceof ServerContract, "Class {$class} is not a server instance.");

        return $server;
    }

    /**
     * @return ConfigRepository
     */
    private function config(): ConfigRepository
    {
        return $this->app->instance()->make(ConfigRepository::class);
    }
}
