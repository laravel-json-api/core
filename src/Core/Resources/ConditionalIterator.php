<?php
/*
 * Copyright 2021 Cloud Creativity Limited
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

namespace LaravelJsonApi\Core\Resources;

use Generator;
use IteratorAggregate;
use JsonSerializable;
use LaravelJsonApi\Contracts\Resources\Skippable;
use function iterator_to_array;

class ConditionalIterator implements IteratorAggregate, JsonSerializable
{

    /**
     * @var iterable
     */
    private iterable $iterator;

    /**
     * ConditionalIterator constructor.
     *
     * @param iterable $iterator
     */
    public function __construct(iterable $iterator)
    {
        $this->iterator = $iterator;
    }

    /**
     * @return Generator
     */
    public function cursor(): Generator
    {
        foreach ($this->iterator as $key => $value) {
            if ($value instanceof Skippable && true === $value->skip()) {
                continue;
            }

            if ($value instanceof ConditionalAttrs) {
                yield from $value;
                continue;
            }

            yield $key => $value;
        }
    }

    /**
     * @return array
     */
    public function all(): array
    {
        return iterator_to_array($this->cursor());
    }

    /**
     * @inheritDoc
     */
    public function getIterator()
    {
        return $this->cursor();
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return $this->all() ?: null;
    }

}
