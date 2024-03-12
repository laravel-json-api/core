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
use LaravelJsonApi\Core\Query\Input\QueryOne;
use LaravelJsonApi\Core\Query\Input\QueryRelated;
use LaravelJsonApi\Core\Query\Input\QueryRelationship;
use LaravelJsonApi\Core\Query\Input\WillQueryOne;

interface QueryOneValidator
{
    /**
     * Make a validator for query parameters when fetching zero-to-one resources.
     *
     * @param QueryOne|WillQueryOne|QueryRelated|QueryRelationship $query
     * @return Validator
     */
    public function make(QueryOne|WillQueryOne|QueryRelated|QueryRelationship $query): Validator;
}
