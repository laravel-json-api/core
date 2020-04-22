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

namespace LaravelJsonApi\Core\Contracts\Encoder;

use LaravelJsonApi\Core\Contracts\Document\DataDocument;
use LaravelJsonApi\Core\Contracts\Document\ResourceObject;

interface DocumentBuilder
{

    /**
     * Set the include paths.
     *
     * @param iterable $includePaths
     * @return $this
     */
    public function withIncludePaths(iterable $includePaths): self;

    /**
     * Set the sparse field-sets.
     *
     * @param array $fieldSets
     * @return $this
     */
    public function withFieldSets(array $fieldSets): self;

    /**
     * Create a document with a resource object as the primary data.
     *
     * @param ResourceObject|null $data
     * @return DataDocument
     */
    public function createResource(?ResourceObject $data): DataDocument;

    /**
     * Create a document with resource objects as the primary data.
     *
     * @param iterable $data
     * @return DataDocument
     */
    public function createResources(iterable $data): DataDocument;
}
