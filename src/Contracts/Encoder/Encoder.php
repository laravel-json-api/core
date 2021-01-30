<?php
/*
 * Copyright 2021 Cloud Creativity Limited
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

namespace LaravelJsonApi\Contracts\Encoder;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

interface Encoder
{

    /**
     * @param Request|null $request
     * @return $this
     */
    public function withRequest($request): self;

    /**
     * @param $includePaths
     * @return $this
     */
    public function withIncludePaths($includePaths): self;

    /**
     * @param $fieldSets
     * @return $this
     */
    public function withFieldSets($fieldSets): self;

    /**
     * Create a compound document with a resource as the top-level data member.
     *
     * @param JsonApiResource|object|null $resource
     * @return JsonApiDocument
     */
    public function withResource(?object $resource): JsonApiDocument;

    /**
     * Create a compound document with a collection of resources as the top-level data member.
     *
     * @param iterable $resources
     * @return JsonApiDocument
     */
    public function withResources(iterable $resources): JsonApiDocument;

    /**
     * Create a compound document for a relationship identifier or identifiers.
     *
     * @param JsonApiResource $resource
     * @param string $fieldName
     * @param JsonApiResource|iterable|null $identifiers
     * @return JsonApiDocument
     */
    public function withIdentifiers(JsonApiResource $resource, string $fieldName, $identifiers): JsonApiDocument;
}
