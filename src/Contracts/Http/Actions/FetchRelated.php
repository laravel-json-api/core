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
use LaravelJsonApi\Core\Responses\RelatedResponse;

interface FetchRelated extends Responsable
{
    /**
     * Set the JSON:API resource type for the action.
     *
     * @param ResourceType|string $type
     * @return $this
     */
    public function withType(ResourceType|string $type): static;

    /**
     * Set the JSON:API resource id for the action, or the model (if bindings have been substituted).
     *
     * @param object|string $idOrModel
     * @return $this
     */
    public function withIdOrModel(object|string $idOrModel): static;

    /**
     * Set the JSON:API field name of the relationship that is being fetched.
     *
     * @param string $fieldName
     * @return $this
     */
    public function withFieldName(string $fieldName): static;

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
     * @return RelatedResponse
     */
    public function execute(Request $request): RelatedResponse;
}
