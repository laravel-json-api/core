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

namespace LaravelJsonApi\Core\Document\Input\Parsers;

use LaravelJsonApi\Core\Document\Input\Values\ResourceObject;
use LaravelJsonApi\Core\Values\ResourceId;
use LaravelJsonApi\Core\Values\ResourceType;

class ResourceObjectParser
{
    /**
     * Parse a resource object from an array.
     *
     * @param array $data
     * @return ResourceObject
     */
    public function parse(array $data): ResourceObject
    {
        assert(isset($data['type']), 'Resource object array must contain a type.');

        return new ResourceObject(
            type: ResourceType::cast($data['type']),
            id: ResourceId::nullable($data['id'] ?? null),
            lid: ResourceId::nullable($data['lid'] ?? null),
            attributes: $data['attributes'] ?? [],
            relationships: $data['relationships'] ?? [],
            meta: $data['meta'] ?? [],
        );
    }
}
