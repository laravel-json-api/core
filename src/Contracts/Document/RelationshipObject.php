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
     * Get the resource linkage.
     *
     * For a to-one relationship, this will return either:
     * - the resource identifier for the related resource; or
     * - `null` if the relationship is empty.
     *
     * For a to-many relationship, this will return either:
     * - an iterable of resource identifiers; or
     * - an empty iterable for an empty relationship.
     *
     * @return ResourceIdentifierObject|iterable|null
     */
    public function data();

    /**
     * Does the relationship have a data member?
     *
     * @return bool
     */
    public function showData(): bool;

    /**
     * Get the related resource(s).
     *
     * For a to-one relationship, this will return either:
     * - the related resource object; or
     * - `null` if the relationship is empty.
     *
     * For a to-many relationship, this will return either:
     * - an iterable of resource objects; or
     * - an empty iterable for an empty relationship.
     *
     * @return ResourceObject|iterable|null
     */
    public function related();

    /**
     * @return Links
     */
    public function links(): Links;

    /**
     * @return Hash
     */
    public function meta(): Hash;
}
