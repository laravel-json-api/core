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
     * - `null` if the relationship is empty; or
     * - a Closure returning any of the above.
     *
     * For a to-many relationship, this will return either:
     * - an iterable of related resources; or
     * - an iterable of related resource identifiers; or
     * - an empty iterable for an empty relationship; or
     * - a Closure returning any of the above.
     *
     * In either case, resource identifier(s) MUST NOT be returned
     * if the related data will be included in a compound document.
     *
     * @return mixed|iterable|null
     */
    public function data();

    /**
     * Does the relationship always have a data member?
     *
     * Returning `true` indicates the relationship always has a data member,
     * even if the related resource is not included in the compound document.
     *
     * Returning `false` indicates that the relationship will only have a data
     * member if the related resource is included in the compound document.
     *
     * It is STRONGLY RECOMMENDED that this method returns `false`: so that
     * resource linkage only exists in compound documents. This follows
     * the pattern used by other JSON:API implementations whereby relationships
     * only contain foreign keys in their data member if the related resource
     * is included in the compound document. As an example, see the Rails
     * implementation.
     *
     * @return bool
     * @see https://jsonapi.org/format/#document-resource-object-linkage
     * @see https://github.com/fotinakis/jsonapi-serializers#compound-documents-and-includes
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
