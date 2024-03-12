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

use LaravelJsonApi\Core\Document\Input\Values\ResourceIdentifier;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Href;
use LaravelJsonApi\Core\Extensions\Atomic\Values\OpCodeEnum;
use LaravelJsonApi\Core\Extensions\Atomic\Values\ParsedHref;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Ref;
use LaravelJsonApi\Core\Values\ResourceType;

class UpdateToOne extends Operation
{
    /**
     * UpdateToOne constructor
     *
     * @param Ref|ParsedHref $target
     * @param ResourceIdentifier|null $data
     * @param array $meta
     */
    public function __construct(
        public readonly Ref|ParsedHref $target,
        public readonly ?ResourceIdentifier $data,
        array $meta = []
    ) {
        parent::__construct(OpCodeEnum::Update, $meta);
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
     * @return bool
     */
    public function isUpdatingRelationship(): bool
    {
        return true;
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
     * @inheritDoc
     */
    public function toArray(): array
    {
        $values = [
            'op' => $this->op->value,
            'href' => $this->href()?->value,
            'ref' => $this->target instanceof Ref ? $this->target->toArray() : null,
            'data' => $this->data?->toArray(),
            'meta' => empty($this->meta) ? null : $this->meta,
        ];

        return array_filter(
            $values,
            static fn (mixed $value, string $key) => $value !== null || $key === 'data',
            ARRAY_FILTER_USE_BOTH,
        );
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        $values = [
            'op' => $this->op,
            'href' => $this->href()?->value,
            'ref' => $this->target instanceof Ref ? $this->target : null,
            'data' => $this->data,
            'meta' => empty($this->meta) ? null : $this->meta,
        ];

        return array_filter(
            $values,
            static fn (mixed $value, string $key) => $value !== null || $key === 'data',
            ARRAY_FILTER_USE_BOTH,
        );
    }
}
