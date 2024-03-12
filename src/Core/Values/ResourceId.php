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

class ResourceId implements Stringable, JsonSerializable
{
    /**
     * @param ResourceId|string $value
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
     * @param ResourceId|string|null $value
     * @return static|null
     */
    public static function nullable(self|string|null $value): ?self
    {
        if ($value !== null) {
            return self::cast($value);
        }

        return null;
    }

    /**
     * @param string|null $value
     * @return bool
     */
    public static function isNotEmpty(?string $value): bool
    {
        return '0' === $value || !empty(trim($value));
    }

    /**
     * ResourceId constructor
     *
     * @param string $value
     */
    public function __construct(public readonly string $value)
    {
        Contracts::assert(
            self::isNotEmpty($this->value),
            'Resource id must be a non-empty string.',
        );
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
     * @param ResourceId $other
     * @return bool
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
