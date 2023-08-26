<?php
/*
 * Copyright 2023 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace LaravelJsonApi\Core\Http\Actions\AttachRelationship;

use Illuminate\Http\Request;
use LaravelJsonApi\Contracts\Resources\Container;
use LaravelJsonApi\Core\Document\Input\Values\ModelOrResourceId;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;

class AttachRelationshipActionInputFactory
{
    /**
     * AttachRelationshipActionInputFactory constructor
     *
     * @param Container $resources
     */
    public function __construct(private readonly Container $resources)
    {
    }

    /**
     * Make an input object for an attach relationship action.
     *
     * @param Request $request
     * @param ResourceType|string $type
     * @param object|string $modelOrResourceId
     * @param string $fieldName
     * @return AttachRelationshipActionInput
     */
    public function make(
        Request $request,
        ResourceType|string $type,
        object|string $modelOrResourceId,
        string $fieldName,
    ): AttachRelationshipActionInput
    {
        $type = ResourceType::cast($type);
        $modelOrResourceId = new ModelOrResourceId($modelOrResourceId);
        $id = $modelOrResourceId->id() ?? $this->resources->idForType(
            $type,
            $modelOrResourceId->modelOrFail(),
        );

        return new AttachRelationshipActionInput(
            $request,
            $type,
            $id,
            $fieldName,
            $modelOrResourceId->model(),
        );
    }
}
