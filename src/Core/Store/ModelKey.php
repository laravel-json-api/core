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

namespace LaravelJsonApi\Core\Store;

class ModelKey
{
    /**
     * Cast a value to a model key.
     *
     * @param ModelKey|string|int $key
     * @return self
     */
    public static function cast(self|string|int $key): self
    {
        if ($key instanceof self) {
            return $key;
        }

        return new self($key);
    }

    /**
     * Cast a value to a model key, unless it is null.
     *
     * @param ModelKey|string|int|null $key
     * @return self|null
     */
    public static function nullable(self|string|int|null $key): ?self
    {
        if ($key === null) {
            return null;
        }

        return self::cast($key);
    }

    /**
     * ModelKey constructor
     *
     * @param string|int $value
     */
    public function __construct(public readonly string|int $value)
    {
    }
}
