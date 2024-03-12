<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
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
