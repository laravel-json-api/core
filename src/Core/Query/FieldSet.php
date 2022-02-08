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

namespace LaravelJsonApi\Core\Query;

use Countable;
use Illuminate\Support\Enumerable;
use InvalidArgumentException;
use IteratorAggregate;
use Traversable;
use UnexpectedValueException;
use function count;
use function explode;
use function is_array;
use function is_null;
use function is_string;

class FieldSet implements IteratorAggregate, Countable
{

    /**
     * @var string
     */
    private string $resourceType;

    /**
     * @var string[]
     */
    private array $fields;

    /**
     * Create a new field set.
     *
     * @param string $resourceType
     * @param Enumerable|array|string|null $fields
     * @return static
     */
    public static function cast(string $resourceType, $fields): self
    {
        if (is_string($fields)) {
            return self::fromString($resourceType, $fields);
        }

        if (is_array($fields) || $fields instanceof Enumerable || is_null($fields)) {
            return self::fromArray($resourceType, $fields ?? []);
        }

        throw new UnexpectedValueException('Unexpected include paths value.');
    }

    /**
     * Create a field set from a string.
     *
     * @param string $resourceType
     * @param string $fields
     * @return static
     */
    public static function fromString(string $resourceType, string $fields): self
    {
        return new self(
            $resourceType,
            !empty($fields) ? explode(',', $fields) : [],
        );
    }

    /**
     * Create a field set from an array.
     *
     * @param string $resourceType
     * @param Enumerable|array $fields
     * @return static
     */
    public static function fromArray(string $resourceType, $fields): self
    {
        if ($fields instanceof Enumerable) {
            $fields = $fields->all();
        }

        return new self($resourceType, $fields);
    }

    /**
     * FieldSet constructor.
     *
     * @param string $resourceType
     * @param string[] $fields
     */
    public function __construct(string $resourceType, array $fields)
    {
        if (empty($resourceType)) {
            throw new InvalidArgumentException('Expecting a non-empty resoruce type.');
        }

        $this->resourceType = $resourceType;
        $this->fields = [];

        foreach ($fields as $field) {
            if (is_string($field) && !empty($field)) {
                $this->fields[] = $field;
                continue;
            }

            throw new InvalidArgumentException('Expecting fields to contain only non-empty strings.');
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return implode(',', $this->fields);
    }

    /**
     * The resource type the field set belongs to.
     *
     * @return string
     */
    public function type(): string
    {
        return $this->resourceType;
    }

    /**
     * The fields to serialize in the output.
     *
     * @return array
     */
    public function fields(): array
    {
        return $this->fields;
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->fields);
    }

    /**
     * @return bool
     */
    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    /**
     * @inheritDoc
     */
    public function getIterator(): Traversable
    {
        yield from $this->fields;
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->fields);
    }

}
