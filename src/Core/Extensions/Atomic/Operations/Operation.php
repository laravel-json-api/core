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

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use LaravelJsonApi\Core\Extensions\Atomic\Values\OpCodeEnum;
use LaravelJsonApi\Core\Extensions\Atomic\Values\Ref;
use LaravelJsonApi\Core\Values\ResourceType;

abstract class Operation implements JsonSerializable, Arrayable
{
    /**
     * @return ResourceType
     */
    abstract public function type(): ResourceType;

    /**
     * @return Ref|null
     */
    abstract public function ref(): ?Ref;

    /**
     * Operation constructor
     *
     * @param OpCodeEnum $op
     * @param array $meta
     */
    public function __construct(public readonly OpCodeEnum $op, public readonly array $meta = [])
    {
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
        return $this->ref()?->relationship;
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
