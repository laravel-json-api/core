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

use Closure;
use InvalidArgumentException;
use IteratorAggregate;
use LaravelJsonApi\Contracts\Resources\Skippable;
use LogicException;
use Traversable;

class ConditionalFields implements IteratorAggregate, Skippable
{

    /**
     * @var bool
     */
    private bool $check;

    /**
     * @var Closure|iterable
     */
    private $values;

    /**
     * ConditionalAttrs constructor.
     *
     * @param bool $bool
     * @param Closure|iterable $values
     */
    public function __construct(bool $bool, $values)
    {
        if (!$values instanceof Closure && !is_iterable($values)) {
            throw new InvalidArgumentException('Expecting an iterable value or Closure.');
        }

        $this->check = $bool;
        $this->values = $values;
    }

    /**
     * Should the attributes be skipped when encoding?
     *
     * @return bool
     */
    public function skip(): bool
    {
        return false === $this->check;
    }

    /**
     * @inheritDoc
     */
    public function getIterator(): Traversable
    {
        if (true === $this->skip()) {
            throw new LogicException('Conditional attributes must not be iterated.');
        }

        foreach ($this->values() as $key => $value) {
            if ($value instanceof Closure) {
                $value = ($value)();
            }

            yield $key => $value;
        }
    }

    /**
     * @return iterable
     */
    public function values(): iterable
    {
        if ($this->values instanceof Closure) {
            $values = ($this->values)();

            if (is_iterable($values)) {
                return $values;
            }

            throw new LogicException('Conditional attributes closure must return an iterable value.');
        }

        return $this->values;
    }

}
