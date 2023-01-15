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

namespace LaravelJsonApi\Core\Support;

use Closure;
use Illuminate\Contracts\Foundation\Application;

class AppResolver
{
    /**
     * @var Closure
     */
    private Closure $resolver;

    /**
     * AppResolver constructor.
     *
     * @param Closure $resolver
     */
    public function __construct(Closure $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * Get the application instance.
     *
     * @return Application
     */
    public function instance(): Application
    {
        return ($this->resolver)();
    }

    /**
     * Get a container resolver.
     *
     * @return ContainerResolver
     */
    public function container(): ContainerResolver
    {
        return new ContainerResolver($this->resolver);
    }
}
