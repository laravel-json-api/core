<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Extensions\Atomic\Operations;

use LaravelJsonApi\Core\Document\Input\Values\ResourceObject;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Href;
use LaravelJsonApi\Core\Extensions\Atomic\Values\OpCodeEnum;
use LaravelJsonApi\Core\Extensions\Atomic\Values\ParsedHref;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Ref;
use LaravelJsonApi\Core\Values\ResourceType;

class Update extends Operation
{
    /**
     * Update constructor
     *
     * @param Ref|ParsedHref|null $target
     * @param ResourceObject $data
     * @param array $meta
     */
    public function __construct(
        public readonly Ref|ParsedHref|null $target,
        public readonly ResourceObject $data,
        array $meta = []
    ) {
        parent::__construct(OpCodeEnum::Update, $meta);
    }

    /**
     * @inheritDoc
     */
    public function type(): ResourceType
    {
        return $this->data->type;
    }

    /**
     * @inheritDoc
     */
    public function ref(): Ref
    {
        if ($this->target instanceof Ref) {
            return $this->target;
        }

        return $this->target?->ref() ?? new Ref(
            type: $this->data->type,
            id: $this->data->id,
            lid: $this->data->lid,
        );
    }

    /**
     * @return Href|null
     */
    public function href(): ?Href
    {
        if ($this->target instanceof ParsedHref) {
            return $this->target->href;
        }

        return null;
    }

    /**
     * @return bool
     */
    public function isUpdating(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        return array_filter([
            'op' => $this->op->value,
            'href' => $this->target instanceof ParsedHref ? $this->target->href->value : null,
            'ref' => $this->target instanceof Ref ? $this->target->toArray() : null,
            'data' => $this->data->toArray(),
            'meta' => empty($this->meta) ? null : $this->meta,
        ], static fn (mixed $value) => $value !== null);
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return array_filter([
            'op' => $this->op,
            'href' => $this->target instanceof ParsedHref ? $this->target : null,
            'ref' => $this->target instanceof Ref ? $this->target : null,
            'data' => $this->data,
            'meta' => empty($this->meta) ? null : $this->meta,
        ], static fn (mixed $value) => $value !== null);
    }
}
