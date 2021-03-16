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

use InvalidArgumentException;
use LaravelJsonApi\Contracts\Schema\Filter;
use LaravelJsonApi\Contracts\Schema\Schema;

class FilterParameter
{

    /**
     * @var string
     */
    private string $key;

    /**
     * @var mixed
     */
    private $value;

    /**
     * FilterParameter constructor.
     *
     * @param string $key
     * @param $value
     */
    public function __construct(string $key, $value)
    {
        if (empty($key)) {
            throw new InvalidArgumentException('Expecting a non-empty string for the filter key.');
        }

        $this->key = $key;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function key(): string
    {
        return $this->key;
    }

    /**
     * @return mixed
     */
    public function value()
    {
        return $this->value;
    }

    /**
     * @param Schema $schema
     * @return bool
     */
    public function existsOnSchema(Schema $schema): bool
    {
        /** @var Filter $filter */
        foreach ($schema->filters() as $filter) {
            if ($this->key() === $filter->key()) {
                return true;
            }
        }

        return false;
    }
}
