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

namespace LaravelJsonApi\Contracts\Resources;

use LaravelJsonApi\Core\Document\Links;

interface JsonApiRelation
{

    /**
     * Get the relation's JSON:API field name.
     *
     * @return string
     */
    public function fieldName(): string;

    /**
     * Get the value of the links member.
     *
     * @return Links
     */
    public function links(): Links;

    /**
     * Get the value of the meta member.
     *
     * @return array|null
     */
    public function meta(): ?array;

    /**
     * Get the value of the data member.
     *
     * @return mixed
     */
    public function data();

    /**
     * Should data always be shown?
     *
     * If this method returns `false`, the relationship's data will only be shown
     * if the client has requested it (via include paths). Returning `true` means
     * the relationship data will always be shown, regardless of what the client
     * has requested.
     *
     * @return bool
     */
    public function showData(): bool;
}
