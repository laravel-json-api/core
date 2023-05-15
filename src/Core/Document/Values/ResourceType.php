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

namespace LaravelJsonApi\Core\Document\Values;

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
}