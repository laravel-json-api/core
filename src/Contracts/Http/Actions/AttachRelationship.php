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

namespace LaravelJsonApi\Contracts\Http\Actions;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Document\Input\Values\ResourceType;
use LaravelJsonApi\Core\Responses\NoContentResponse;
use LaravelJsonApi\Core\Responses\RelationshipResponse;

interface AttachRelationship extends Responsable
{
    /**
     * Set the target for the action.
     *
     * A model can be set if the bindings have been substituted, or if the action is being
     * run manually.
     *
     * @param ResourceType|string $type
     * @param object|string $idOrModel
     * @param string $fieldName
     * @return $this
     */
    public function withTarget(
        ResourceType|string $type,
        object|string $idOrModel,
        string $fieldName,
    ): static;

    /**
     * Set the object that implements controller hooks.
     *
     * @param object|null $target
     * @return $this
     */
    public function withHooks(?object $target): static;

    /**
     * Execute the action and return a response.
     *
     * @param Request $request
     * @return RelationshipResponse|NoContentResponse
     */
    public function execute(Request $request): RelationshipResponse|NoContentResponse;
}
