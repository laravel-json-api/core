<?php
/**
 * Copyright 2020 Cloud Creativity Limited
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

namespace LaravelJsonApi\Core\Contracts\Document;

use LaravelJsonApi\Core\Document\Links;
use LaravelJsonApi\Core\Json\Hash;

interface ResourceObject
{

    /**
     * @return string
     */
    public function type(): string;

    /**
     * @return string
     */
    public function id(): string;

    /**
     * Get the resource's identifier object.
     *
     * The identifier is used for resource linkage in a compound document,
     * and may contain meta in addition to the type and id.
     *
     * @return ResourceIdentifierObject
     */
    public function identifier(): ResourceIdentifierObject;

    /**
     * @return iterable|null
     */
    public function attributes(): iterable;

    /**
     * @return iterable|null
     */
    public function relationships(): iterable;

    /**
     * @return Links
     */
    public function links(): Links;

    /**
     * @return Hash
     */
    public function meta(): Hash;
}
