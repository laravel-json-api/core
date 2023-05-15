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

namespace LaravelJsonApi\Core\Document\Values;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

class ResourceObject implements JsonSerializable, Arrayable
{
    /**
     * ResourceObject constructor
     *
     * @param ResourceIdentifier $identifier
     * @param array $attributes
     * @param array $relationships
     * @param array $meta
     */
    public function __construct(
        public readonly ResourceIdentifier $identifier,
        public readonly array $attributes = [],
        public readonly array $relationships = [],
        public readonly array $meta = [],
    )
    {
    }

    /**
     * Return a new instance with the id set.
     *
     * @param ResourceId|string $id
     * @return self
     */
    public function withId(ResourceId|string $id): self
    {
        return new self(
            identifier: $this->identifier->withId($id),
            attributes: $this->attributes,
            relationships: $this->relationships,
            meta: $this->meta,
        );
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        $arr = $this->identifier->toArray();

        if (!empty($this->attributes)) {
            $arr['attributes'] = $this->attributes;
        }

        if (!empty($this->relationships)) {
            $arr['relationships'] = $this->relationships;
        }

        if (!empty($this->meta)) {
            $arr['meta'] = $this->meta;
        }

        return $arr;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        $json = $this->identifier->jsonSerialize();

        if (!empty($this->attributes)) {
            $json['attributes'] = $this->attributes;
        }

        if (!empty($this->relationships)) {
            $json['relationships'] = $this->relationships;
        }

        if (!empty($this->meta)) {
            $json['meta'] = $this->meta;
        }

        return $json;
    }
}