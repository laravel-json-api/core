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

namespace LaravelJsonApi\Contracts\Validation;

use Illuminate\Http\Request;

interface Factory
{
    /**
     * Set the request context for the validation.
     *
     * @param Request|null $request
     * @return $this
     */
    public function withRequest(?Request $request): static;

    /**
     * Get a validator to use when querying zero-to-many resources.
     *
     * @return QueryManyValidator
     */
    public function queryMany(): QueryManyValidator;

    /**
     * Get a validator to use when querying zero-to-one resources.
     *
     * @return QueryOneValidator
     */
    public function queryOne(): QueryOneValidator;

    /**
     * Get a validator to use when creating a resource.
     *
     * @return CreationValidator
     */
    public function store(): CreationValidator;

    /**
     * Get a validator to use when updating a resource.
     *
     * @return UpdateValidator
     */
    public function update(): UpdateValidator;

    /**
     * Get a validator to use when deleting a resource.
     *
     * Deletion validation is optional. Implementations can return `null`
     * if deletion validation can be skipped.
     *
     * @return DeletionValidator|null
     */
    public function destroy(): ?DeletionValidator;

    /**
     * Get a validator to use when modifying a resources' relationship.
     *
     * @return RelationshipValidator
     */
    public function relation(): RelationshipValidator;
}
