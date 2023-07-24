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

use LaravelJsonApi\Core\Document\Input\Values\ResourceIdentifier;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Href;
use LaravelJsonApi\Core\Extensions\Atomic\Values\OpCodeEnum;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Ref;

class UpdateToOne extends Operation
{
    /**
     * UpdateToOne constructor
     *
     * @param Ref|Href $target
     * @param ResourceIdentifier|null $data
     * @param array $meta
     */
    public function __construct(
        Ref|Href $target,
        public readonly ?ResourceIdentifier $data,
        array $meta = []
    ) {
        parent::__construct(
            op: OpCodeEnum::Update,
            target: $target,
            meta: $meta,
        );
    }

    /**
     * @return bool
     */
    public function isUpdatingRelationship(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        $values = [
            'op' => $this->op->value,
            'href' => $this->href()?->value,
            'ref' => $this->ref()?->toArray(),
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
            'href' => $this->href(),
            'ref' => $this->ref(),
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
