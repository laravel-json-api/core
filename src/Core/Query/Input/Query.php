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

namespace LaravelJsonApi\Core\Query\Input;

use LaravelJsonApi\Core\Values\ResourceType;

abstract class Query
{
    /**
     * Query constructor
     *
     * @param QueryCodeEnum $code
     * @param ResourceType $type
     * @param array $parameters
     */
    public function __construct(
        public readonly QueryCodeEnum $code,
        public readonly ResourceType $type,
        public readonly array $parameters = [],
    ) {
    }

    /**
     * Is this querying zero-to-many resources?
     *
     * @return bool
     */
    public function isMany(): bool
    {
        return $this->code === QueryCodeEnum::Many;
    }

    /**
     * Is this querying zero-to-one resource?
     *
     * @return bool
     */
    public function isOne(): bool
    {
        return $this->code === QueryCodeEnum::One;
    }

    /**
     * Is this querying related resources in a relationship?
     *
     * @return bool
     */
    public function isRelated(): bool
    {
        return $this->code === QueryCodeEnum::Related;
    }

    /**
     * Is this querying resource identifiers in a relationship?
     *
     * @return bool
     */
    public function isRelationship(): bool
    {
        return $this->code === QueryCodeEnum::Relationship;
    }

    /**
     * Is this querying a related resources or resource identifiers?
     *
     * @return bool
     */
    public function isRelatedOrRelationship(): bool
    {
        return match($this->code) {
            QueryCodeEnum::Related, QueryCodeEnum::Relationship => true,
            default => false,
        };
    }

    /**
     * Get the relationship field name that is being queried.
     *
     * @return string|null
     */
    public function getFieldName(): ?string
    {
        return null;
    }
}
