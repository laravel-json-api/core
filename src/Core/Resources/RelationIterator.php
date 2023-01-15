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

namespace LaravelJsonApi\Core\Resources;

use Generator;
use IteratorAggregate;
use LaravelJsonApi\Contracts\Resources\JsonApiRelation;
use RuntimeException;
use Traversable;

class RelationIterator implements IteratorAggregate
{
    /**
     * @var JsonApiResource
     */
    private JsonApiResource $resource;

    /**
     * RelationIterator constructor.
     *
     * @param JsonApiResource $resource
     */
    public function __construct(JsonApiResource $resource)
    {
        $this->resource = $resource;
    }

    /**
     * @inheritDoc
     */
    public function getIterator(): Traversable
    {
        return $this->cursor();
    }

    /**
     * @return array
     */
    public function all(): array
    {
        return iterator_to_array($this);
    }

    /**
     * @return Generator
     */
    public function cursor(): Generator
    {
        foreach ($this->values() as $value) {
            if ($value instanceof ConditionalField) {
                $value = $value->value();
            }

            if ($value instanceof JsonApiRelation) {
                yield $value->fieldName() => $value;
                continue;
            }

            throw new RuntimeException('Expecting a JSON:API resource relation.');
        }
    }

    /**
     * @return Generator
     */
    private function values(): Generator
    {
        foreach ($this->resource->relationships(null) as $value) {
            if ($value instanceof ConditionalFields) {
                yield from $value->values();
                continue;
            }

            yield $value;
        }
    }

}
