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

use LaravelJsonApi\Core\Extensions\Atomic\Values\Href;
use LaravelJsonApi\Core\Extensions\Atomic\Values\OpCodeEnum;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Ref;

class Delete extends Operation
{
    /**
     * Delete constructor
     *
     * @param Href|Ref $target
     * @param array $meta
     */
    public function __construct(Href|Ref $target, array $meta = [])
    {
        parent::__construct(
            op: OpCodeEnum::Remove,
            target: $target,
            meta: $meta,
        );
    }

    /**
     * @return bool
     */
    public function isDeleting(): bool
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
            'href' => $this->href()?->value,
            'ref' => $this->ref()?->toArray(),
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
            'ref' => $this->ref(),
            'meta' => empty($this->meta) ? null : $this->meta,
        ], static fn (mixed $value) => $value !== null);
    }
}
