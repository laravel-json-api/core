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

namespace LaravelJsonApi\Core\Bus\Queries\Concerns;

use InvalidArgumentException;
use RuntimeException;

trait Relatable
{
    use Identifiable;

    /**
     * @var string|null
     */
    private ?string $fieldName = null;

    /**
     * Return a new instance with the JSON:API field name set.
     *
     * @param string $field
     * @return $this
     */
    public function withFieldName(string $field): static
    {
        if (empty($field)) {
            throw new InvalidArgumentException('Expecting a non-empty field name.');
        }

        $copy = clone $this;
        $copy->fieldName = $field;

        return $copy;
    }

    /**
     * Get the JSON:API field name.
     *
     * @return string
     */
    public function fieldName(): string
    {
        if ($this->fieldName) {
            return $this->fieldName;
        }

        throw new RuntimeException('Expecting a field name to be set.');
    }
}
