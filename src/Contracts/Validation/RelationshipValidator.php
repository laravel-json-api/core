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

use Illuminate\Contracts\Validation\Validator;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\UpdateToMany;
use LaravelJsonApi\Core\Extensions\Atomic\Operations\UpdateToOne;

interface RelationshipValidator
{
    /**
     * Extract validation data from the update relationship operation.
     *
     * @param UpdateToOne|UpdateToMany $operation
     * @param object $model
     * @return array
     */
    public function extract(UpdateToOne|UpdateToMany $operation, object $model): array;

    /**
     * Make a validator for the update relationship operation.
     *
     * @param UpdateToOne|UpdateToMany $operation
     * @param object $model
     * @return Validator
     */
    public function make(UpdateToOne|UpdateToMany $operation, object $model): Validator;
}
