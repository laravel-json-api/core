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

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Href;
use LaravelJsonApi\Core\Extensions\Atomic\Values\OpCodeEnum;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Ref;

abstract class Operation implements JsonSerializable, Arrayable
{
    /**
     * Operation constructor
     *
     * @param OpCodeEnum $op
     * @param Ref|Href|null $target
     * @param array $meta
     */
    public function __construct(
        public readonly OpCodeEnum $op,
        public readonly Ref|Href|null $target = null,
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
     * Is the operation creating a resource?
     *
     * @return bool
     */
    public function isCreating(): bool
    {
        return false;
    }

    /**
     * Is the operation updating a resource?
     *
     * @return bool
     */
    public function isUpdating(): bool
    {
        return false;
    }

    /**
     * Is the operation creating or updating a resource?
     *
     * @return bool
     */
    public function isCreatingOrUpdating(): bool
    {
        return $this->isCreating() || $this->isUpdating();
    }

    /**
     * Is the operation deleting a resource?
     *
     * @return bool
     */
    public function isDeleting(): bool
    {
        return false;
    }

    /**
     * Get the relationship field name that is being modified.
     *
     * @return string|null
     */
    public function getFieldName(): ?string
    {
        if ($ref = $this->ref()) {
            return $ref->relationship;
        }

        return $this->href()?->getRelationshipName();
    }

    /**
     * Is the operation updating a relationship?
     *
     * @return bool
     */
    public function isUpdatingRelationship(): bool
    {
        return false;
    }

    /**
     * Is the operation attaching resources to a relationship?
     *
     * @return bool
     */
    public function isAttachingRelationship(): bool
    {
        return false;
    }

    /**
     * Is the operation detaching resources from a relationship?
     *
     * @return bool
     */
    public function isDetachingRelationship(): bool
    {
        return false;
    }

    /**
     * Is the operation modifying a relationship?
     *
     * @return bool
     */
    public function isModifyingRelationship(): bool
    {
        return $this->isUpdatingRelationship() ||
            $this->isAttachingRelationship() ||
            $this->isDetachingRelationship();
    }
}
