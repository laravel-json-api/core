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
use LaravelJsonApi\Contracts\Http\Controllers\Hooks\ShowRelatedImplementation;
use LaravelJsonApi\Core\Bus\Queries\Concerns\Relatable;
use LaravelJsonApi\Core\Bus\Queries\IsRelatable;
use LaravelJsonApi\Core\Bus\Queries\Query;
use LaravelJsonApi\Core\Document\Input\Values\ResourceId;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;

class FetchRelatedQuery extends Query implements IsRelatable
{
    use Relatable;

    /**
     * @var ShowRelatedImplementation|null
     */
    private ?ShowRelatedImplementation $hooks = null;

    /**
     * Fluent constructor.
     *
     * @param Request|null $request
     * @param ResourceType|string $type
     * @param ResourceId|string|null $id
     * @param string|null $fieldName
     * @return self
     */
    public static function make(
        ?Request $request,
        ResourceType|string $type,
        ResourceId|string|null $id = null,
        ?string $fieldName = null,
    ): self
    {
        return new self($request, $type, $id, $fieldName);
    }

    /**
     * FetchRelatedQuery constructor
     *
     * @param Request|null $request
     * @param ResourceType|string $type
     * @param ResourceId|string|null $id
     * @param string|null $fieldName
     */
    public function __construct(
        ?Request $request,
        ResourceType|string $type,
        ResourceId|string|null $id = null,
        ?string $fieldName = null,
    ) {
        parent::__construct($request, $type);
        $this->id = ResourceId::nullable($id);
        $this->fieldName = $fieldName ?: null;
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
