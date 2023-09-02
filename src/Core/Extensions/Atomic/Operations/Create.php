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

namespace LaravelJsonApi\Core\Extensions\Atomic\Operations;

use LaravelJsonApi\Core\Document\Input\Values\ResourceObject;
use LaravelJsonApi\Core\Extensions\Atomic\Values\OpCodeEnum;
use LaravelJsonApi\Core\Extensions\Atomic\Values\ParsedHref;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Ref;
use LaravelJsonApi\Core\Values\ResourceType;

class Create extends Operation
{
    /**
     * Create constructor
     *
     * @param ParsedHref|null $target
     * @param ResourceObject $data
     * @param array $meta
     */
    public function __construct(
        public readonly ParsedHref|null $target,
        public readonly ResourceObject $data,
        array $meta = []
    ) {
        assert($target === null || $target->type->equals($data->type), 'Expecting href to match resource type.');
        assert($this->target?->id === null, 'Expecting no resource id in href.');

        parent::__construct(op: OpCodeEnum::Add, meta: $meta);
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
    public function ref(): ?Ref
    {
        return null;
    }

    /**
     * @return bool
     */
    public function isCreating(): bool
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
            'href' => $this->target?->href->value,
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
            'href' => $this->target?->href,
            'data' => $this->data,
            'meta' => empty($this->meta) ? null : $this->meta,
        ], static fn (mixed $value) => $value !== null);
    }
}
