<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Http\Actions\Input;

use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Query\QueryParameters;
use LaravelJsonApi\Core\Http\Hooks\HooksImplementation;
use LaravelJsonApi\Core\Values\ResourceType;
use RuntimeException;

abstract class ActionInput
{
    /**
     * @var QueryParameters|null
     */
    private ?QueryParameters $queryParameters = null;

    /**
     * @var HooksImplementation|null
     */
    private ?HooksImplementation $hooks = null;

    /**
     * ActionInput constructor
     *
     * @param Request $request
     * @param ResourceType $type
     */
    public function __construct(protected readonly Request $request, protected readonly ResourceType $type)
    {
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
    public function withQueryParameters(QueryParameters $query): static
    {
        $copy = clone $this;
        $copy->queryParameters = $query;

        return $copy;
    }

    /**
     * @return QueryParameters
     */
    public function queryParameters(): QueryParameters
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
