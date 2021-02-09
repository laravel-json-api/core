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

interface Relation extends Field
{

    /**
     * Get the relationship's field name as it appears in a URI.
     *
     * @return string
     */
    public function uriName(): string;

    /**
     * Is this a to-one relation?
     *
     * @return bool
     */
    public function toOne(): bool;

    /**
     * Is this a to-many relation?
     *
     * @return bool
     */
    public function toMany(): bool;

    /**
     * Get the inverse resource type.
     *
     * If the relation is polymorphic, it MUST implement
     * the `PolymorphicRelation` interface and return a
     * psuedo-type from this method.
     *
     * For example, if an `images` resource has an `imageable`
     * relation to which either a `posts` or `users` resource
     * could be related. The `inverse()` method would return
     * `imageables` and the `inverseTypes()` method would
     * return: `['posts', 'users']`.
     *
     * @return string
     */
    public function inverse(): string;

    /**
     * Is the relation allowed as an include path?
     *
     * @return bool
     */
    public function isIncludePath(): bool;

    /**
     * Get additional filters for the relation.
     *
     * Filters returned by this method are additional to the filters
     * that exist on the inverse resource type.
     *
     * @return Filter[]|iterable
     */
    public function filters(): iterable;

    /**
     * Is the relation value required when validating an update request?
     *
     * When updating resources, the JSON:API specification says:
     *
     * "If a request does not include all of the relationships for a resource,
     * the server MUST interpret the missing relationships as if they were included
     * with their current values. It MUST NOT interpret them as null or empty values."
     *
     * This means we need to merge existing relationship values with those provided
     * by the client for an update request. However, it would be extremely inefficient
     * for us to read the value of every relation. For example, a `posts` resource
     * could have hundreds of `comments`, which are not required for validation.
     *
     * Therefore only the values of relations that return `true` for this method
     * will be extracted and merged for an update request.
     *
     * @return bool
     * @see https://jsonapi.org/format/#crud-updating-resource-relationships
     */
    public function isValidated(): bool;
}
