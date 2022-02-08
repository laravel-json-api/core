<?php
/*
 * Copyright 2022 Cloud Creativity Limited
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

namespace LaravelJsonApi\Core\Server\Concerns;

use LaravelJsonApi\Contracts\Server\Server;
use LaravelJsonApi\Core\Facades\JsonApi;

trait ServerAware
{

    /**
     * @var string|null
     */
    private ?string $server = null;

    /**
     * Use a named server, or the default server if name is empty.
     *
     * @param string|null $name
     * @return $this
     */
    public function withServer(?string $name): self
    {
        $this->server = $name ?: null;

        return $this;
    }

    /**
     * Get the server the response is from.
     *
     * @return Server
     */
    protected function server(): Server
    {
        return JsonApi::server($this->server);
    }

    /**
     * Get the server the response if from, if one exists.
     *
     * If the server name has not been set manually, this method returns
     * the server that is bound into the service container - if one has been
     * bound. A server will not be bound in the service container if the
     * JSON:API middleware has not been run.
     *
     * If the server name has been manually set, then the method will always
     * return the named server.
     *
     * @return Server|null
     */
    protected function serverIfExists(): ?Server
    {
        if (null === $this->server) {
            return JsonApi::serverIfExists();
        }

        return $this->server();
    }
}
