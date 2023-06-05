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
use LaravelJsonApi\Core\Extensions\Atomic\Values\Href;
use LaravelJsonApi\Core\Extensions\Atomic\Values\OpCodeEnum;

class Store extends Operation
{
    /**
     * Store constructor
     *
     * @param Href $target
     * @param ResourceObject $data
     * @param array $meta
     */
    public function __construct(
        Href $target,
        public readonly ResourceObject $data,
        array $meta = []
    ) {
        parent::__construct(
            op: OpCodeEnum::Add,
            target: $target,
            meta: $meta,
        );
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
        return [
            'op' => $this->op->value,
            'href' => $this->href()->value,
            'data' => $this->data->toArray(),
        ];
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return [
            'op' => $this->op,
            'href' => $this->target,
            'data' => $this->data,
        ];
    }
}
