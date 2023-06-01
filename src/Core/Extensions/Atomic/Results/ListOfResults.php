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
