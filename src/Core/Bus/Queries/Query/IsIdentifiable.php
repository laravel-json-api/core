<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Bus\Queries\Query;

use LaravelJsonApi\Core\Values\ResourceId;

interface IsIdentifiable
{
    /**
     * Get the resource id for the query.
     *
     * @return ResourceId
     */
    public function id(): ResourceId;

    /**
     * Get the model for the query, if there is one.
     *
     * @return object|null
     */
    public function model(): ?object;

    /**
     * Get the model for the query, or fail if there isn't one.
     *
     * @return object
     */
    public function modelOrFail(): object;

    /**
     * Return a new instance with the model set.
     *
     * @param object|null $model
     * @return static
     */
    public function withModel(?object $model): static;
}
