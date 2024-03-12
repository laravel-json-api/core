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
use LaravelJsonApi\Core\Extensions\Atomic\Operations\Create;

interface CreationValidator
{
    /**
     * Extract validation data from the store operation.
     *
     * @param Create $operation
     * @return array
     */
    public function extract(Create $operation): array;

    /**
     * Make a validator for the store operation.
     *
     * @param Create $operation
     * @return Validator
     */
    public function make(Create $operation): Validator;
}
