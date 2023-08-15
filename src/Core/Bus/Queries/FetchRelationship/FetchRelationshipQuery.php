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

namespace LaravelJsonApi\Core\Bus\Queries\FetchRelationship;

use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Http\Controllers\Hooks\ShowRelationshipImplementation;
use LaravelJsonApi\Core\Bus\Queries\Query\IsRelatable;
use LaravelJsonApi\Core\Bus\Queries\Query\Query;
use LaravelJsonApi\Core\Bus\Queries\Query\Relatable;
use LaravelJsonApi\Core\Document\Input\Values\ResourceId;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;

class FetchRelationshipQuery extends Query implements IsRelatable
{
    use Relatable;

    /**
     * @var ShowRelationshipImplementation|null
     */
    private ?ShowRelationshipImplementation $hooks = null;

    /**
     * Fluent constructor.
     *
     * @param Request|null $request
     * @param ResourceType|string $type
     * @param ResourceId|string $id
     * @param string $fieldName
     * @return self
     */
    public static function make(
        ?Request $request,
        ResourceType|string $type,
        ResourceId|string $id,
        string $fieldName,
    ): self
    {
        return new self($request, $type, $id, $fieldName);
    }

    /**
     * FetchRelationshipQuery constructor
     *
     * @param Request|null $request
     * @param ResourceType|string $type
     * @param ResourceId|string $id
     * @param string $fieldName
     */
    public function __construct(
        ?Request $request,
        ResourceType|string $type,
        ResourceId|string $id,
        string $fieldName,
    ) {
        parent::__construct($request, $type);
        $this->id = ResourceId::cast($id);
        $this->fieldName = $fieldName ?: null;
    }

    /**
     * Set the hooks implementation.
     *
     * @param ShowRelationshipImplementation|null $hooks
     * @return $this
     */
    public function withHooks(?ShowRelationshipImplementation $hooks): self
    {
        $copy = clone $this;
        $copy->hooks = $hooks;

        return $copy;
    }

    /**
     * @return ShowRelationshipImplementation|null
     */
    public function hooks(): ?ShowRelationshipImplementation
    {
        return $this->hooks;
    }
}
