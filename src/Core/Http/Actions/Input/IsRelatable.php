<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Http\Actions\Input;

use LaravelJsonApi\Core\Query\Input\QueryRelated;
use LaravelJsonApi\Core\Query\Input\QueryRelationship;

interface IsRelatable extends IsIdentifiable
{
    /**
     * Get the JSON:API field name for the target relationship.
     *
     * @return string
     */
    public function fieldName(): string;

    /**
     * @return QueryRelated|QueryRelationship
     */
    public function query(): QueryRelated|QueryRelationship;
}
