<?php
/**
 * Copyright 2020 Cloud Creativity Limited
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
use InvalidArgumentException;
use IteratorAggregate;
use UnexpectedValueException;
use function explode;
use function implode;
use function is_string;

class RelationshipPath implements IteratorAggregate, Countable
{

    /**
     * @var string[]
     */
    private $names;

    /**
     * @param RelationshipPath|string $value
     * @return RelationshipPath
     */
    public static function cast($value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        if (is_string($value)) {
            return self::fromString($value);
        }

        throw new UnexpectedValueException('Unexpected relationship path value.');
    }

    /**
     * @param string $path
     * @return RelationshipPath
     */
    public static function fromString(string $path): self
    {
        if (!empty($path)) {
            return new self(...explode('.', $path));
        }

        throw new UnexpectedValueException('Expecting a non-empty string.');
    }

    /**
     * IncludePath constructor.
     *
     * @param string ...$paths
     */
    public function __construct(string ...$paths)
    {
        if (empty($paths)) {
            throw new InvalidArgumentException('Expecting at least one relationship path.');
        }

        $this->names = $paths;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * Fluent to string method.
     *
     * @return string
     */
    public function toString(): string
    {
        return implode('.', $this->names);
    }

    /**
     * @return array
     */
    public function names(): array
    {
        return $this->names;
    }

    /**
     * @inheritDoc
     */
    public function getIterator()
    {
        yield from $this->names;
    }

    /**
     * @inheritDoc
     */
    public function count()
    {
        return count($this->names);
    }

}