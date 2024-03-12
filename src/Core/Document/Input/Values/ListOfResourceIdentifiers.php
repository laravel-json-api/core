<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
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
