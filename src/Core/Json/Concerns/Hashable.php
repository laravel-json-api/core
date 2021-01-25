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

namespace LaravelJsonApi\Core\Json\Concerns;

use Generator;
use InvalidArgumentException;
use LaravelJsonApi\Core\Support\Arr;
use function iterator_to_array;

trait Hashable
{

    /**
     * @var array
     */
    private array $value = [];

    /**
     * @var callable|null
     */
    private $serializer;

    /**
     * @var int|null
     */
    private ?int $fieldNameOrder = null;

    /**
     * @inheritDoc
     */
    public function offsetExists($offset)
    {
        return Arr::exists($this->value, $offset);
    }

    /**
     * @inheritDoc
     */
    public function offsetGet($offset)
    {
        return $this->value[$offset];
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value)
    {
        $this->value[$offset] = $value;
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset($offset)
    {
        Arr::forget($this->value, $offset);
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->value);
    }

    /**
     * @return bool
     */
    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    /**
     * Mark the hash as having camel-case keys.
     *
     * @return $this
     */
    public function camelize(): self
    {
        return $this->serializeUsing(
            static fn(array $values) => Arr::camelize($values)
        );
    }

    /**
     * Mark the hash as having dash-case keys.
     *
     * @return $this
     */
    public function dasherize(): self
    {
        return $this->serializeUsing(
            static fn(array $values) => Arr::dasherize($values)
        );
    }

    /**
     * Mark the hash as having snake-case keys.
     *
     * We use underscore because this correctly converts both camel and
     * dash case keys to snake case.
     *
     * @return $this
     */
    public function snake(): self
    {
        return $this->underscore();
    }

    /**
     * Mark the hash as having underscored (snake case) keys.
     *
     * @return $this
     */
    public function underscore(): self
    {
        return $this->serializeUsing(
            static fn(array $values) => Arr::underscore($values)
        );
    }

    /**
     * Mark the hash as using the provided case for keys.
     *
     * @param string $case
     * @return $this
     */
    public function useCase(string $case): self
    {
        switch ($case) {
            case 'snake' :
                $this->snake();
                break;

            case 'underscore' :
                $this->underscore();
                break;

            case 'dash' :
            case 'dasherize' :
                $this->dasherize();
                break;

            case 'camel' :
            case 'camelize' :
                $this->camelize();
                break;

            default :
                throw new InvalidArgumentException('Unexpected case.');
        }

        return $this;
    }

    /**
     * Use the callback to serialize the array.
     *
     * Serialization is deferred until when the hash is traversed.
     * This ensures that the cost of serialization is only incurred
     * if the hash is iterated.
     *
     * @param callable $callback
     * @return $this
     */
    public function serializeUsing(callable $callback): self
    {
        $this->serializer = $callback;

        return $this;
    }

    /**
     * Iterate through fields names in a sorted order.
     *
     * Sorting is deferred until when the hash is traversed.
     * This ensures that the cost of sorting keys is only incurred
     * if the hash is iterated.
     *
     * @param int $flags
     *      the flags to use when sorting.
     * @return $this
     * @see ksort()
     */
    public function sorted(int $flags = 0): self
    {
        $this->fieldNameOrder = $flags;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function count()
    {
        return count($this->value);
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        if ($this->isNotEmpty()) {
            return $this->all();
        }

        return null;
    }

    /**
     * @return Generator
     */
    public function cursor(): Generator
    {
        if ($fn = $this->serializer) {
            $values = $fn($this->value);
        } else {
            $values = $this->value;
        }

        if (is_int($this->fieldNameOrder)) {
            ksort($values, $this->fieldNameOrder);
        }

        yield from $values;
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

}
