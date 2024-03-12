<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Values;

use JsonSerializable;
use LaravelJsonApi\Contracts\Support\Stringable;
use LaravelJsonApi\Core\Support\Contracts;

class ResourceType implements Stringable, JsonSerializable
{
    /**
     * @param ResourceType|string $value
     * @return static
     */
    public static function cast(self|string $value): self
    {
        if (is_string($value)) {
            return new self($value);
        }

        return $value;
    }

    /**
     * ResourceType constructor.
     *
     * @param string $value
     */
    public function __construct(public readonly string $value)
    {
        Contracts::assert(!empty(trim($this->value)), 'Resource type must be a non-empty string.');
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * @inheritDoc
     */
    public function toString(): string
    {
        return $this->value;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): string
    {
        return $this->value;
    }

    /**
     * @param ResourceType $other
     * @return bool
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
