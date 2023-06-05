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
