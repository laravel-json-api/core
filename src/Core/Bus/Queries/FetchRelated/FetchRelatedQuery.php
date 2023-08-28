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

namespace LaravelJsonApi\Core\Bus\Queries\FetchRelated;

use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Http\Hooks\ShowRelatedImplementation;
use LaravelJsonApi\Core\Bus\Queries\Query\Identifiable;
use LaravelJsonApi\Core\Bus\Queries\Query\IsRelatable;
use LaravelJsonApi\Core\Bus\Queries\Query\Query;
use LaravelJsonApi\Core\Query\Input\QueryRelated;
use LaravelJsonApi\Core\Values\ResourceId;

class FetchRelatedQuery extends Query implements IsRelatable
{
    use Identifiable;

    /**
     * @var ShowRelatedImplementation|null
     */
    private ?ShowRelatedImplementation $hooks = null;

    /**
     * Fluent constructor.
     *
     * @param Request|null $request
     * @param QueryRelated $input
     * @return self
     */
    public static function make(?Request $request, QueryRelated $input): self
    {
        return new self($request, $input);
    }

    /**
     * FetchRelatedQuery constructor
     *
     * @param Request|null $request
     * @param QueryRelated $input
     */
    public function __construct(
        ?Request $request,
        private readonly QueryRelated $input,
    ) {
        parent::__construct($request);
    }

    /**
     * @return ResourceId
     */
    public function id(): ResourceId
    {
        return $this->input->id;
    }

    /**
     * @return string
     */
    public function fieldName(): string
    {
        return $this->input->fieldName;
    }

    /**
     * @return QueryRelated
     */
    public function input(): QueryRelated
    {
        return $this->input;
    }

    /**
     * Set the hooks implementation.
     *
     * @param ShowRelatedImplementation|null $hooks
     * @return $this
     */
    public function withHooks(?ShowRelatedImplementation $hooks): self
    {
        $copy = clone $this;
        $copy->hooks = $hooks;

        return $copy;
    }

    /**
     * @return ShowRelatedImplementation|null
     */
    public function hooks(): ?ShowRelatedImplementation
    {
        return $this->hooks;
    }
}
