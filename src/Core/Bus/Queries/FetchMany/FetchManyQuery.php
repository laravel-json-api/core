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

namespace LaravelJsonApi\Core\Bus\Queries\FetchMany;

use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Http\Controllers\Hooks\IndexImplementation;
use LaravelJsonApi\Core\Bus\Queries\Query;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;

class FetchManyQuery extends Query
{
    /**
     * @var IndexImplementation|null
     */
    private ?IndexImplementation $hooks = null;

    /**
     * Fluent constructor.
     *
     * @param Request|null $request
     * @param ResourceType|string $type
     * @return self
     */
    public static function make(?Request $request, ResourceType|string $type): self
    {
        return new self($request, $type);
    }


    /**
     * Set the hooks implementation.
     *
     * @param IndexImplementation|null $hooks
     * @return $this
     */
    public function withHooks(?IndexImplementation $hooks): self
    {
        $copy = clone $this;
        $copy->hooks = $hooks;

        return $copy;
    }

    /**
     * @return IndexImplementation|null
     */
    public function hooks(): ?IndexImplementation
    {
        return $this->hooks;
    }
}