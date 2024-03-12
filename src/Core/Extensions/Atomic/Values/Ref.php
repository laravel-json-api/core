<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Extensions\Atomic\Values;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use LaravelJsonApi\Core\Support\Contracts;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;

class Ref implements JsonSerializable, Arrayable
{
    /**
     * Ref constructor
     *
     * @param ResourceType $type
     * @param ResourceId|null $id
     * @param ResourceId|null $lid
     * @param string|null $relationship
     */
    public function __construct(
        public readonly ResourceType $type,
        public readonly ResourceId|null $id = null,
        public readonly ResourceId|null $lid = null,
        public readonly string|null $relationship = null,
    ) {
        Contracts::assert($this->id !== null || $this->lid !== null, 'Ref must have an id or lid.');

        Contracts::assert(
            $this->id === null || $this->lid === null,
            'Ref cannot have both an id and lid.',
        );

        Contracts::assert(
            $this->relationship === null || !empty(trim($this->relationship)),
            'Relationship must be a non-empty string if provided.',
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
            'relationship' => $this->relationship,
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
            'relationship' => $this->relationship,
        ], static fn(mixed $value): bool => $value !== null);
    }
}
