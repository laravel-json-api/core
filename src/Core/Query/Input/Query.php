<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
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
