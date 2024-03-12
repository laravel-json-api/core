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
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Delete;

interface DeletionValidator
{
    /**
     * Extract validation data for a delete operation.
     *
     * @param Delete $operation
     * @param object $model
     * @return array
     */
    public function extract(Delete $operation, object $model): array;

    /**
     * Make a validator for the delete operation.
     *
     * @param Delete $operation
     * @param object $model
     * @return Validator
     */
    public function make(Delete $operation, object $model): Validator;
}
