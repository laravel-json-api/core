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

use Countable;
use Illuminate\Contracts\Support\Arrayable;
use IteratorAggregate;
use JsonSerializable;
use LaravelJsonApi\Core\Support\Contracts;
use Traversable;

class ListOfOperations implements IteratorAggregate, Countable, JsonSerializable, Arrayable
{
    /**
     * @var Operation[]
     */
    private readonly array $ops;

    /**
     * ListOfOperations constructor
     *
     * @param Operation ...$operations
     */
    public function __construct(Operation ...$operations)
    {
        Contracts::assert(!empty($operations), 'Operation list must have at least one operation.');

        $this->ops = $operations;
    }

    /**
     * @inheritDoc
     */
    public function getIterator(): Traversable
    {
        yield from $this->ops;
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->ops);
    }

    /**
     * @return Operation[]
     */
    public function all(): array
    {
        return $this->ops;
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        return array_map(
            static fn(Operation $op): array => $op->toArray(),
            $this->ops,
        );
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return $this->ops;
    }
}
