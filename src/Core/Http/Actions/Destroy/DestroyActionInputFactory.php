<?php
/*
 * Copyright 2024 Cloud Creativity Limited
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Http\Actions\Destroy;

use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Resources\Container;
use LaravelJsonApi\Core\Values\ModelOrResourceId;
use LaravelJsonApi\Core\Values\ResourceType;

class DestroyActionInputFactory
{
    /**
     * DestroyActionInputFactory constructor
     *
     * @param Container $resources
     */
    public function __construct(private readonly Container $resources)
    {
    }

    /**
     * Make an input object for a destroy action.
     *
     * @param Request $request
     * @param ResourceType|string $type
     * @param object|string $modelOrResourceId
     * @return DestroyActionInput
     */
    public function make(
        Request $request,
        ResourceType|string $type,
        object|string $modelOrResourceId
    ): DestroyActionInput
    {
        $type = ResourceType::cast($type);
        $modelOrResourceId = new ModelOrResourceId($modelOrResourceId);
        $id = $modelOrResourceId->id() ?? $this->resources->idForType(
            $type,
            $modelOrResourceId->modelOrFail(),
        );

        return new DestroyActionInput(
            $request,
            $type,
            $id,
            $modelOrResourceId->model(),
        );
    }
}
