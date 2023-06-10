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

namespace LaravelJsonApi\Core\Http\Actions;

use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Query\QueryParameters;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;
use LaravelJsonApi\Core\Http\Controllers\Hooks\HooksImplementation;
use RuntimeException;

abstract class ActionInput
{
    /**
     * @var ResourceType
     */
    private readonly ResourceType $type;

    /**
     * @var QueryParameters|null
     */
    private ?QueryParameters $queryParameters = null;

    /**
     * @var HooksImplementation|null
     */
    private ?HooksImplementation $hooks = null;

    /**
     * Action constructor
     *
     * @param Request $request
     */
    public function __construct(private readonly Request $request, ResourceType|string $type)
    {
        $this->type = ResourceType::cast($type);
    }

    /**
     * @return Request
     */
    public function request(): Request
    {
        return $this->request;
    }

    /**
     * @return ResourceType
     */
    public function type(): ResourceType
    {
        return $this->type;
    }

    /**
     * @param QueryParameters $query
     * @return static
     */
    public function withQuery(QueryParameters $query): static
    {
        $copy = clone $this;
        $copy->queryParameters = $query;

        return $copy;
    }

    /**
     * @return QueryParameters
     */
    public function query(): QueryParameters
    {
        if ($this->queryParameters) {
            return $this->queryParameters;
        }

        throw new RuntimeException('Expecting validated query parameters to be set on action.');
    }

    /**
     * Set the hooks for the action.
     *
     * @param object|null $target
     * @return $this
     */
    public function withHooks(?object $target): static
    {
        $copy = clone $this;
        $copy->hooks = $target ? new HooksImplementation($target) : null;

        return $copy;
    }

    /**
     * Get the hooks for the action.
     *
     * @return HooksImplementation|null
     */
    public function hooks(): ?HooksImplementation
    {
        return $this->hooks;
    }
}
