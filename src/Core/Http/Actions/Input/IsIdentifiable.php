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

use LaravelJsonApi\Core\Values\ResourceId;

interface IsIdentifiable
{
    /**
     * Get the resource id.
     *
     * @return ResourceId
     */
    public function id(): ResourceId;

    /**
     * Get the model, if there is one.
     *
     * @return object|null
     */
    public function model(): ?object;

    /**
     * Get the model, or fail if there isn't one.
     *
     * @return object
     */
    public function modelOrFail(): object;

    /**
     * Return a new instance with the model set.
     *
     * @param object $model
     * @return static
     */
    public function withModel(object $model): static;
}
