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

use LaravelJsonApi\Core\Document\Input\Values\ListOfResourceIdentifiers;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Href;
use LaravelJsonApi\Core\Extensions\Atomic\Values\OpCodeEnum;
use LaravelJsonApi\Core\Extensions\Atomic\Values\ParsedHref;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Ref;
use LaravelJsonApi\Core\Values\ResourceType;

class UpdateToMany extends Operation
{
    /**
     * UpdateToMany constructor
     *
     * @param OpCodeEnum $op
     * @param Ref|ParsedHref $target
     * @param ListOfResourceIdentifiers $data
     * @param array $meta
     */
    public function __construct(
        OpCodeEnum $op,
        public readonly Ref|ParsedHref $target,
        public readonly ListOfResourceIdentifiers $data,
        array $meta = []
    ) {
        parent::__construct($op, $meta);
    }

    /**
     * @inheritDoc
     */
    public function type(): ResourceType
    {
        return $this->ref()->type;
    }

    /**
     * @inheritDoc
     */
    public function ref(): Ref
    {
        if ($this->target instanceof Ref) {
            return $this->target;
        }

        return $this->target->ref();
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
     * @return string
     */
    public function getFieldName(): string
    {
        $name = parent::getFieldName();

        assert(!empty($name), 'Expecting a field name to be set.');

        return $name;
    }

    /**
     * @return bool
     */
    public function isAttachingRelationship(): bool
    {
        return OpCodeEnum::Add === $this->op;
    }

    /**
     * @return bool
     */
    public function isUpdatingRelationship(): bool
    {
        return OpCodeEnum::Update === $this->op;
    }

    /**
     * @return bool
     */
    public function isDetachingRelationship(): bool
    {
        return OpCodeEnum::Remove === $this->op;
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        return array_filter([
            'op' => $this->op->value,
            'href' => $this->href()?->value,
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
            'href' => $this->href(),
            'ref' => $this->target instanceof Ref ? $this->target : null,
            'data' => $this->data,
            'meta' => empty($this->meta) ? null : $this->meta,
        ], static fn (mixed $value) => $value !== null);
    }
}
