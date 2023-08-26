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

namespace LaravelJsonApi\Core\Document\Input\Values;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use LaravelJsonApi\Core\Support\Contracts;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;

class ResourceObject implements JsonSerializable, Arrayable
{
    /**
     * ResourceObject constructor
     *
     * @param ResourceType $type
     * @param ResourceId|null $id
     * @param ResourceId|null $lid
     * @param array $attributes
     * @param array $relationships
     * @param array $meta
     */
    public function __construct(
        public readonly ResourceType $type,
        public readonly ResourceId|null $id = null,
        public readonly ResourceId|null $lid = null,
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
        Contracts::assert($this->id === null, 'Resource object already has an id.');

        return new self(
            type: $this->type,
            id: ResourceId::cast($id),
            lid: $this->lid,
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
        return array_filter([
            'type' => $this->type->value,
            'id' => $this->id?->value,
            'lid' => $this->lid?->value,
            'attributes' => $this->attributes ?: null,
            'relationships' => $this->relationships ?: null,
            'meta' => $this->meta ?: null,
        ], static fn(mixed $value): bool => $value !== null);
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return array_filter([
            'type' => $this->type,
            'id' => $this->id,
            'lid' => $this->lid,
            'attributes' => $this->attributes ?: null,
            'relationships' => $this->relationships ?: null,
            'meta' => $this->meta ?: null,
        ], static fn(mixed $value): bool => $value !== null);
    }
}
