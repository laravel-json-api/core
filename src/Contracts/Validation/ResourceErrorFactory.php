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
use LaravelJsonApi\Contracts\Schema\Schema;
use LaravelJsonApi\Core\Document\ErrorList;

interface ResourceErrorFactory
{
    /**
     * Make JSON:API errors for the provided validator.
     *
     * @param Schema $schema
     * @param Validator $validator
     * @return ErrorList
     */
    public function make(Schema $schema, Validator $validator): ErrorList;
}
