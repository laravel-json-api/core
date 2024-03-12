<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Document\Input\Values;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use LaravelJsonApi\Core\Support\Contracts;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;

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
