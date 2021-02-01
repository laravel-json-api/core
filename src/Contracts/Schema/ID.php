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

namespace LaravelJsonApi\Contracts\Schema;

interface ID extends Field, Sortable
{

    /**
     * Get the model key for the id.
     *
     * @return string|null
     */
    public function key(): ?string;

    /**
     * Get the regex pattern for the ID field.
     *
     * @return string
     */
    public function pattern(): string;

    /**
     * Does the value match the pattern?
     *
     * @param string $value
     * @return bool
     */
    public function match(string $value): bool;

    /**
     * Does the resource accept client generated ids?
     *
     * @return bool
     */
    public function acceptsClientIds(): bool;
}
