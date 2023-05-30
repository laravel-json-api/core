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

class ResourceIdentifier implements JsonSerializable, Arrayable
{
    /**
     * @param ResourceType $type
     * @param ResourceId|null $id
     * @param ResourceId|null $lid
     * @param array $meta
     */
    public function __construct(
        public readonly ResourceType $type,
        public readonly ResourceId|null $id = null,
        public readonly ResourceId|null $lid = null,
        public readonly array $meta = [],
    ) {
        Contracts::assert(
            $this->id !== null || $this->lid !== null,
            'Resource identifier must have an id or lid.',
        );
    }

    /**
     * Return a new instance with the provided id set.
     *
     * @param ResourceId|string $id
     * @return self
     */
    public function withId(ResourceId|string $id): self
    {
        Contracts::assert($this->id === null, 'Resource identifier already has an id.');

        return new self(
            type: $this->type,
            id: ResourceId::cast($id),
            lid: $this->lid,
            meta: $this->meta,
        );
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        $arr = ['type' => $this->type->value];

        if ($this->id) {
            $arr['id'] = $this->id->value;
        }

        if ($this->lid) {
            $arr['lid'] = $this->lid->value;
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
        $json = ['type' => $this->type];

        if ($this->id) {
            $json['id'] = $this->id;
        }

        if ($this->lid) {
            $json['lid'] = $this->lid;
        }

        if (!empty($this->meta)) {
            $json['meta'] = $this->meta;
        }

        return $json;
    }
}
