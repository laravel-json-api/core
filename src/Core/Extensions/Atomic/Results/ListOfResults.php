<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Extensions\Atomic\Results;

use Countable;
use IteratorAggregate;
use LaravelJsonApi\Core\Support\Contracts;
use Traversable;

class ListOfResults implements IteratorAggregate, Countable
{
    /**
     * @var Result[]
     */
    private readonly array $results;

    /**
     * ListOfResults constructor
     *
     * @param Result ...$results
     */
    public function __construct(Result ...$results)
    {
        Contracts::assert(!empty($results), 'Result list must have at least one result.');

        $this->results = $results;
    }

    /**
     * @inheritDoc
     */
    public function getIterator(): Traversable
    {
        yield from $this->results;
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->results);
    }

    /**
     * @return Result[]
     */
    public function all(): array
    {
        return $this->results;
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        foreach ($this->results as $result) {
            if ($result->isNotEmpty()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return bool
     */
    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }
}
