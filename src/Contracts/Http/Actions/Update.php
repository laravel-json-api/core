<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Contracts\Http\Actions;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Responses\DataResponse;
use LaravelJsonApi\Core\Values\ResourceType;

interface Update extends Responsable
{
    /**
     * Set the target for the action.
     *
     * A model can be set if the bindings have been substituted, or if the action is being
     * run manually.
     *
     * @param ResourceType|string $type
     * @param object|string $idOrModel
     * @return $this
     */
    public function withTarget(ResourceType|string $type, object|string $idOrModel): static;

    /**
     * Set the object that implements controller hooks.
     *
     * @param object|null $target
     * @return $this
     */
    public function withHooks(?object $target): static;

    /**
     * Execute the action and return the JSON:API data response.
     *
     * @param Request $request
     * @return DataResponse
     */
    public function execute(Request $request): DataResponse;
}
