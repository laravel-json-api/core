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

namespace LaravelJsonApi\Core\Document\Input\Values;

use Countable;
use Illuminate\Contracts\Support\Arrayable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

class ListOfResourceIdentifiers implements IteratorAggregate, Countable, Arrayable, JsonSerializable
{
    /**
     * @var ResourceIdentifier[]
     */
    private array $identifiers;

    /**
     * ListOfResourceIdentifiers constructor
     *
     * @param ResourceIdentifier ...$identifiers
     */
    public function __construct(ResourceIdentifier ...$identifiers)
    {
        $this->identifiers = $identifiers;
    }

    /**
     * @inheritDoc
     */
    public function getIterator(): Traversable
    {
        yield from $this->identifiers;
    }

    /**
     * @return ResourceIdentifier[]
     */
    public function all(): array
    {
        return $this->identifiers;
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->identifiers);
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->identifiers);
    }

    /**
     * @return bool
     */
    public function isNotEmpty(): bool
    {
        return !empty($this->identifiers);
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        return array_map(
            static fn(ResourceIdentifier $identifier): array => $identifier->toArray(),
            $this->identifiers,
        );
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return $this->identifiers;
    }
}