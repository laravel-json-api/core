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

namespace LaravelJsonApi\Core\Bus\Queries\FetchOne;

use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Http\Controllers\Hooks\ShowImplementation;
use LaravelJsonApi\Core\Bus\Queries\Concerns\Identifiable;
use LaravelJsonApi\Core\Bus\Queries\IsIdentifiable;
use LaravelJsonApi\Core\Bus\Queries\Query;
use LaravelJsonApi\Core\Document\Input\Values\ResourceId;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;

class FetchOneQuery extends Query implements IsIdentifiable
{
    use Identifiable;

    /**
     * @var ShowImplementation|null
     */
    private ?ShowImplementation $hooks = null;

    /**
     * Fluent constructor.
     *
     * @param Request|null $request
     * @param ResourceType|string $type
     * @param ResourceId|string|null $id
     * @return self
     */
    public static function make(
        ?Request $request,
        ResourceType|string $type,
        ResourceId|string|null $id = null
    ): self
    {
        return new self($request, $type, $id);
    }

    /**
     * FetchOneQuery constructor
     *
     * @param Request|null $request
     * @param ResourceType|string $type
     * @param ResourceId|string|null $id
     */
    public function __construct(
        ?Request $request,
        ResourceType|string $type,
        ResourceId|string|null $id = null,
    ) {
        parent::__construct($request, $type);
        $this->id = ResourceId::nullable($id);
    }

    /**
     * Set the hooks implementation.
     *
     * @param ShowImplementation|null $hooks
     * @return $this
     */
    public function withHooks(?ShowImplementation $hooks): self
    {
        $copy = clone $this;
        $copy->hooks = $hooks;

        return $copy;
    }

    /**
     * @return ShowImplementation|null
     */
    public function hooks(): ?ShowImplementation
    {
        return $this->hooks;
    }
}
