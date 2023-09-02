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
use LaravelJsonApi\Core\Extensions\Atomic\Values\ParsedHref;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Ref;
use LaravelJsonApi\Core\Values\ResourceType;

class Delete extends Operation
{
    /**
     * Delete constructor
     *
     * @param ParsedHref|Ref $target
     * @param array $meta
     */
    public function __construct(public readonly ParsedHref|Ref $target, array $meta = [])
    {
        assert($this->target instanceof Ref || $target->id !== null);

        parent::__construct(
            op: OpCodeEnum::Remove,
            meta: $meta,
        );
    }

    /**
     * @inheritDoc
     */
    public function type(): ResourceType
    {
        return $this->ref()->type;
    }

    /**
     * @return Ref
     */
    public function ref(): Ref
    {
        if ($this->target instanceof Ref) {
            return $this->target;
        }

        $ref = $this->target->ref();

        assert($ref !== null, 'Expecting delete operation to have a target resource reference.');

        return $ref;
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
    public function isDeleting(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        $href = $this->href();

        return array_filter([
            'op' => $this->op->value,
            'href' => $href?->value,
            'ref' => $href ? null : $this->target->toArray(),
            'meta' => empty($this->meta) ? null : $this->meta,
        ], static fn (mixed $value) => $value !== null);
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        $href = $this->href();

        return array_filter([
            'op' => $this->op,
            'href' => $href,
            'ref' => $href ? null : $this->target,
            'meta' => empty($this->meta) ? null : $this->meta,
        ], static fn (mixed $value) => $value !== null);
    }
}
