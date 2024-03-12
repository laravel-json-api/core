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
use LaravelJsonApi\Core\Query\Input\QueryMany;
use LaravelJsonApi\Core\Query\Input\QueryRelated;
use LaravelJsonApi\Core\Query\Input\QueryRelationship;

interface QueryManyValidator
{
    /**
     * Make a validator for query parameters when fetching zero-to-many resources.
     *
     * @param QueryMany|QueryRelated|QueryRelationship $query
     * @return Validator
     */
    public function make(QueryMany|QueryRelated|QueryRelationship $query): Validator;
}
