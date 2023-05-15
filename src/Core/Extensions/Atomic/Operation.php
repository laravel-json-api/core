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

namespace LaravelJsonApi\Core\Extensions\Atomic;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use LaravelJsonApi\Core\Document\Values\ResourceObject;

abstract class Operation implements JsonSerializable, Arrayable
{
    /**
     * Operation constructor
     *
     * @param OpCodeEnum $op
     * @param Ref|Href|null $target
     * @param ResourceObject|null $data
     * @param array $meta
     */
    public function __construct(
        public readonly OpCodeEnum $op,
        public readonly Ref|Href|null $target = null,
        public readonly ResourceObject|null $data = null,
        public readonly array $meta = [],
    ) {
    }

    /**
     * @return Ref|null
     */
    public function ref(): ?Ref
    {
        if ($this->target instanceof Ref) {
            return $this->target;
        }

        return null;
    }

    /**
     * @return Href|null
     */
    public function href(): ?Href
    {
        if ($this->target instanceof Href) {
            return $this->target;
        }

        return null;
    }

    /**
     * @return bool
     */
    public function isCreating(): bool
    {
        return false;
    }

    /**
     * @return bool
     */
    public function isUpdating(): bool
    {
        return false;
    }

    /**
     * @return bool
     */
    public function isCreatingOrUpdating(): bool
    {
        return $this->isCreating() || $this->isUpdating();
    }

    /**
     * @return bool
     */
    public function isDeleting(): bool
    {
        return false;
    }

    /**
     * @return string|null
     */
    public function getFieldName(): ?string
    {
        return $this->ref()?->relationship;
    }

    /**
     * @return bool
     */
    public function isUpdatingRelationship(): bool
    {
        return false;
    }

    /**
     * @return bool
     */
    public function isAttachingRelationship(): bool
    {
        return false;
    }

    /**
     * @return bool
     */
    public function isDetachingRelationship(): bool
    {
        return false;
    }

    /**
     * @return bool
     */
    public function isModifyingRelationship(): bool
    {
        return $this->isUpdatingRelationship() ||
            $this->isAttachingRelationship() ||
            $this->isDetachingRelationship();
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        $arr = ['op' => $this->op->value];

        if ($this->target instanceof Ref) {
            $arr['ref'] = $this->target->toArray();
        }

        if ($this->target instanceof Href) {
            $arr['href'] = $this->target->value;
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
        $json = ['op' => $this->op];

        if ($this->target instanceof Ref) {
            $json['ref'] = $this->target;
        }

        if ($this->target instanceof Href) {
            $json['href'] = $this->target;
        }

        if (!empty($this->meta)) {
            $json['meta'] = $this->meta;
        }

        return $json;
    }
}