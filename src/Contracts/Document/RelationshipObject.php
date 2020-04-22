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

namespace LaravelJsonApi\Core\Contracts\Document;

use LaravelJsonApi\Core\Document\Links;
use LaravelJsonApi\Core\Json\Hash;

interface RelationshipObject
{

    /**
     * Get the related resource.
     *
     * For a to-one relationship, this will return either:
     * - the related resource; or
     * - a resource identifier for the related resource; or
     * - `null` if the relationship is empty.
     *
     * For a to-many relationship, this will return either:
     * - an iterable of related resources; or
     * - an iterable of related resource identifiers; or
     * - an empty iterable for an empty relationship.
     *
     * @return mixed|iterable|null
     */
    public function data();

    /**
     * Does the relationship have a data member?
     *
     * @return bool
     */
    public function showData(): bool;

    /**
     * @return Links
     */
    public function links(): Links;

    /**
     * @return bool
     */
    public function hasLinks(): bool;

    /**
     * @return Hash
     */
    public function meta(): Hash;

    /**
     * @return bool
     */
    public function hasMeta(): bool;
}
