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

namespace LaravelJsonApi\Core\Extensions\Atomic\Values;

use JsonSerializable;
use LaravelJsonApi\Contracts\Support\Stringable;
use LaravelJsonApi\Core\Support\Contracts;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;

class ParsedHref implements JsonSerializable, Stringable
{
    /**
     * @param Href $href
     * @param ResourceType $type
     * @param ResourceId|null $id
     * @param string|null $relationship
     */
    public function __construct(
        public readonly Href $href,
        public readonly ResourceType $type,
        public readonly ResourceId|null $id = null,
        public readonly string|null $relationship = null,
    ) {
        Contracts::assert(
            $this->relationship === null || $this->id !== null,
            'Expecting a resource id with a relationship name.',
        );
    }

    /**
     * @return Ref|null
     */
    public function ref(): ?Ref
    {
        if ($this->id) {
            return new Ref(
                type: $this->type,
                id: $this->id,
                relationship: $this->relationship,
            );
        }

        return null;
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
        return $this->href->toString();
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): string
    {
        return $this->href->jsonSerialize();
    }
}
