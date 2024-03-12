<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Extensions\Atomic\Values;

use JsonSerializable;
use LaravelJsonApi\Contracts\Support\Stringable;
use LaravelJsonApi\Core\Support\Contracts;

class Href implements JsonSerializable, Stringable
{
    /**
     * Fluent constructor.
     *
     * @param string $value
     * @return static
     */
    public static function make(string $value): self
    {
        return new self($value);
    }

    /**
     * Href constructor
     *
     * @param string $value
     */
    public function __construct(public readonly string $value)
    {
        Contracts::assert(!empty(trim($this->value)));
    }

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        return $this->value;
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
     * @param Href $other
     * @return bool
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
